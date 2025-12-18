<?php

namespace Lagdo\DbAdmin\Db\Page\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function file_get_contents;
use function function_exists;
use function iconv;
use function preg_match;
use function substr;

trait InputFieldTrait
{
    /**
     * @param TableFieldEntity $field
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    private function getInputFieldExpression(TableFieldEntity $field,
        string $value, string $function): string
    {
        $fieldName = $this->driver->escapeId($field->name);
        $expression = $this->driver->quote($value);

        if (preg_match('~^(now|getdate|uuid)$~', $function)) {
            return "$function()";
        }
        if (preg_match('~^current_(date|timestamp)$~', $function)) {
            return $function;
        }
        if (preg_match('~^([+-]|\|\|)$~', $function)) {
            return "$fieldName $function $expression";
        }
        if (preg_match('~^[+-] interval$~', $function)) {
            return "$fieldName $function " .
                (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) &&
                    $this->driver->jush() !== "pgsql" ? $value : $expression);
        }
        if (preg_match('~^(addtime|subtime|concat)$~', $function)) {
            return "$function($fieldName, $expression)";
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
    protected function getUnconvertedFieldValue(TableFieldEntity $field,
        string $value, string $function = ''): string
    {
        if ($function === 'SQL') {
            return $value; // SQL injection
        }

        $expression = $this->getInputFieldExpression($field, $value, $function);
        return $this->driver->unconvertField($field, $expression);
    }

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
}
