<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_sum;
use function file_get_contents;
use function function_exists;
use function iconv;
use function is_array;
use function is_string;
use function json_decode;
use function substr;

trait QueryInputTrait
{
    use InputFieldTrait;

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
     * @param mixed $value
     *
     * @return false|int|string
     */
    private function getEnumFieldValue($value)
    {
        return match(true) {
            $value === -1 => false,
            $value === '' => 'NULL',
            default => +$value,
        };
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string|false
     */
    private function getOrigFieldValue(TableFieldEntity $field)
    {
        return preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) === false ?
            false : $this->driver->escapeId($field->name);
    }

    /**
     * @param mixed $value
     *
     * @return array|false
     */
    private function getJsonFieldValue($value)
    {
        //! Report errors
        return !is_array($value = json_decode($value, true)) ? false : $value;
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
        //! report errors
        return !is_string($file) ? false : $this->driver->quoteBinary($file);
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

        return match(true) {
            $field->autoIncrement && $value === '' => null,
            $function === 'NULL' => 'NULL',
            $field->type === 'enum' => $this->getEnumFieldValue($value),
            $function === 'orig' => $this->getOrigFieldValue($field),
            $field->type === 'set' => array_sum((array) $value),
            $function == 'json' => $this->getJsonFieldValue($value),
            preg_match('~blob|bytea|raw|file~', $field->type) => $this->getBinaryFieldValue($field),
            default => $this->getUnconvertedFieldValue($field, $value, $function),
        };
    }
}
