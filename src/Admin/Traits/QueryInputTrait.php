<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function file_get_contents;
use function substr;
use function function_exists;
use function iconv;
use function json_decode;
use function is_array;
use function preg_match;
use function is_string;
use function array_sum;

trait QueryInputTrait
{
    /**
     * Get INI boolean value
     *
     * @param string $ini
     *
     * @return bool
     */
    abstract public function iniBool(string $ini): bool;

    /**
     * @param array $file
     * @param string $key
     * @param bool $decompress
     *
     * @return string
     */
    private function readFileContent(array $file, string $key, bool $decompress): string
    {
        $name = $file['name'][$key];
        $tmpName = $file['tmp_name'][$key];
        $content = file_get_contents($decompress && preg_match('~\.gz$~', $name) ?
            "compress.zlib://$tmpName" : $tmpName); //! may not be reachable because of open_basedir
        if (!$decompress) {
            return $content;
        }
        $start = substr($content, 0, 3);
        if (function_exists('iconv') && preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs)) {
            // not ternary operator to save memory
            return iconv('utf-16', 'utf-8', $content) . "\n\n";
        }
        if ($start == "\xEF\xBB\xBF") { // UTF-8 BOM
            return substr($content, 3) . "\n\n";
        }
        return $content;
    }

    /**
     * Get file contents from $_FILES
     *
     * @param string $key
     * @param bool $decompress
     *
     * @return string|null
     */
    private function getFileContents(string $key, bool $decompress = false)
    {
        $file = $_FILES[$key];
        if (!$file) {
            return null;
        }
        foreach ($file as $key => $val) {
            $file[$key] = (array) $val;
        }
        $queries = '';
        foreach ($file['error'] as $key => $error) {
            if (($error)) {
                return $error;
            }
            $queries .= $this->readFileContent($file, $key, $decompress);
        }
        //! Support SQL files not ending with semicolon
        return $queries;
    }

    /**
     * @param TableFieldEntity $field
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    private function getInputFieldExpression(TableFieldEntity $field, string $value, string $function): string
    {
        $expression = $this->driver->quote($value);
        if (preg_match('~^(now|getdate|uuid)$~', $function)) {
            return "$function()";
        }
        if (preg_match('~^current_(date|timestamp)$~', $function)) {
            return $function;
        }
        if (preg_match('~^([+-]|\|\|)$~', $function)) {
            return $this->driver->escapeId($field->name) . " $function $expression";
        }
        if (preg_match('~^[+-] interval$~', $function)) {
            return $this->driver->escapeId($field->name) . " $function " .
                (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) ? $value : $expression);
        }
        if (preg_match('~^(addtime|subtime|concat)$~', $function)) {
            return "$function(" . $this->driver->escapeId($field->name) . ", $expression)";
        }
        if (preg_match('~^(md5|sha1|password|encrypt)$~', $function)) {
            return "$function($expression)";
        }
        return $expression;
    }

    /**
     * @param TableFieldEntity $field Single field from fields()
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    protected function getUnconvertedFieldValue(TableFieldEntity $field, string $value, string $function = ''): string
    {
        if ($function === 'SQL') {
            return $value; // SQL injection
        }
        $expression = $this->getInputFieldExpression($field, $value, $function);
        return $this->driver->unconvertField($field, $expression);
    }

    /**
     * @param mixed $value
     *
     * @return false|int|string
     */
    private function getEnumFieldValue($value)
    {
        if ($value === -1) {
            return false;
        }
        if ($value === '') {
            return 'NULL';
        }
        return +$value;
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string|false
     */
    private function getOrigFieldValue(TableFieldEntity $field)
    {
        if (preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) === false) {
            return false;
        }
        return $this->driver->escapeId($field->name);
    }

    /**
     * @param mixed $value
     *
     * @return array|false
     */
    private function getJsonFieldValue($value)
    {
        if (!is_array($value = json_decode($value, true))) {
            return false; //! Report errors
        }
        return $value;
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string|false
     */
    private function getBinaryFieldValue(TableFieldEntity $field)
    {
        if (!$this->iniBool('file_uploads')) {
            return false;
        }
        $idf = $this->driver->bracketEscape($field->name);
        $file = $this->getFileContents("fields-$idf");
        if (!is_string($file)) {
            return false; //! report errors
        }
        return $this->driver->quoteBinary($file);
    }

    /**
     * Process edit input field
     *
     * @param TableFieldEntity $field
     * @param array $inputs The user inputs
     *
     * @return array|false|float|int|string|null
     */
    public function processInput(TableFieldEntity $field, array $inputs)
    {
        $idf = $this->driver->bracketEscape($field->name);
        $function = $inputs['function'][$idf] ?? '';
        $value = $inputs['fields'][$idf];
        if ($field->autoIncrement && $value === '') {
            return null;
        }
        if ($function === 'NULL') {
            return 'NULL';
        }
        if ($field->type === 'enum') {
            return $this->getEnumFieldValue($value);
        }
        if ($function === 'orig') {
            return $this->getOrigFieldValue($field);
        }
        if ($field->type === 'set') {
            return array_sum((array) $value);
        }
        if ($function == 'json') {
            return $this->getJsonFieldValue($value);
        }
        if (preg_match('~blob|bytea|raw|file~', $field->type)) {
            return $this->getBinaryFieldValue($field);
        }
        return $this->getUnconvertedFieldValue($field, $value, $function);
    }
}
