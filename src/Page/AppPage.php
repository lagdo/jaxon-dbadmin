<?php

namespace Lagdo\DbAdmin\Db\Page;

use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function file_get_contents;
use function function_exists;
use function iconv;
use function max;
use function preg_match;
use function strlen;
use function strtoupper;
use function substr;

class AppPage
{
    /**
     * The constructor
     *
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(public DriverInterface $driver, protected Utils $utils)
    {}

    /**
     * Name in title and navigation
     *
     * @return string
     */
    public function name(): string
    {
        return '<span class="jaxon_dbadmin_name">Jaxon DbAdmin</span>';
    }

    /**
     * Get a target="_blank" attribute
     *
     * @return string
     */
    public function blankTarget(): string
    {
        return ' target="_blank" rel="noreferrer noopener"';
    }

    /**
     * Get escaped error message
     *
     * @return string
     */
    public function error(): string
    {
        return $this->utils->html($this->driver->error());
    }

    /**
     * Table caption used in navigation and headings
     *
     * @param TableEntity $table
     *
     * @return string
     */
    public function tableName(TableEntity $table): string
    {
        return $this->utils->html($table->name);
    }

    /**
     * Field caption used in select and edit
     *
     * @param TableFieldEntity $field Single field returned from fields()
     * @param int $order Order of column in select
     *
     * @return string
     */
    public function fieldName(TableFieldEntity $field, /** @scrutinizer ignore-unused */ int $order = 0): string
    {
        return '<span title="' . $this->utils->html($field->fullType) . '">' .
            $this->utils->html($field->name) . '</span>';
    }

    /**
     * Check if field should be shortened
     *
     * @param TableFieldEntity $field
     *
     * @return bool
     */
    public function isShortable(TableFieldEntity $field): bool
    {
        $pattern = '~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~';
        return preg_match($pattern, $field->type) > 0;
    }

    /**
     * Apply SQL function
     *
     * @param string $function
     * @param string $column escaped column identifier
     *
     * @return string
     */
    public function applySqlFunction(string $function, string $column): string
    {
        if (!$function) {
            return $column;
        }
        if ($function === 'unixepoch') {
            return "DATETIME($column, '$function')";
        }
        if ($function === 'count distinct') {
            return "COUNT(DISTINCT $column)";
        }
        return strtoupper($function) . "($column)";
    }

    /**
     * Value printed in select table
     *
     * @param mixed $value HTML-escaped value to print
     * @param string $type Field type
     * @param mixed $original Original value before escaping
     *
     * @return string
     */
    private function getSelectFieldValue($value, string $type, $original): string
    {
        return match(true) {
            $value === null => '<i>NULL</i>',
            preg_match('~char|binary|boolean~', $type) &&
                !preg_match('~var~', $type) => "<code>$value</code>",
            preg_match('~blob|bytea|raw|file~', $type) &&
                !$this->utils->str->isUtf8($value) => '<i>' .
                    $this->utils->trans->lang('%d byte(s)', strlen($original)) . '</i>',
            preg_match('~json~', $type) => "<code>$value</code>",
            $this->utils->isMail($value) => '<a href="' .
                $this->utils->html("mailto:$value") . '">' . $value . '</a>',
            // IE 11 and all modern browsers hide referrer
            $this->utils->isUrl($value) => '<a href="' . $this->utils->html($value) .
                '"' . $this->blankTarget() . '>' . $value . '</a>',
            default => $value,
        };
    }

    /**
     * Format value to use in select
     *
     * @param TableFieldEntity $field
     * @param int|string|null $textLength
     * @param mixed $value
     *
     * @return string
     */
    public function selectValue(TableFieldEntity $field, $textLength, $value): string
    {
        // if (\is_array($value)) {
        //     $expression = '';
        //     foreach ($value as $k => $v) {
        //         $expression .= '<tr>' . ($value != \array_values($value) ?
        //             '<th>' . $this->utils->html($k) :
        //             '') . '<td>' . $this->selectValue($field, $v, $textLength);
        //     }
        //     return "<table cellspacing='0'>$expression</table>";
        // }
        // if (!$link) {
        //     $link = $this->selectLink($value, $field);
        // }
        $expression = $value;
        if (!empty($expression)) {
            if (!$this->utils->str->isUtf8($expression)) {
                $expression = "\0"; // htmlspecialchars of binary data returns an empty string
            } elseif ($textLength != '' && $this->isShortable($field)) {
                // usage of LEFT() would reduce traffic but complicate query -
                // expected average speedup: .001 s VS .01 s on local network
                $expression = $this->utils->str->shortenUtf8($expression, max(0, +$textLength));
            } else {
                $expression = $this->utils->html($expression);
            }
        }
        return $this->getSelectFieldValue($expression, $field->type, $value);
    }

    /**
     * @param TableFieldEntity $field
     * @param int $textLength
     * @param mixed $value
     *
     * @return array
     */
    public function getFieldValue(TableFieldEntity $field, int $textLength, mixed $value): array
    {
        /*if ($value != "" && (!isset($email_fields[$key]) || $email_fields[$key] != "")) {
            //! filled e-mails can be contained on other pages
            $email_fields[$key] = ($this->page->isMail($value) ? $names[$key] : "");
        }*/
        return [
            // 'id',
            'text' => preg_match('~text|lob~', $field->type),
            'value' => $this->selectValue($field, $textLength, $value),
            // 'editable' => false,
        ];
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string
     */
    public function getTableFieldType(TableFieldEntity $field): string
    {
        $type = $this->utils->str->html($field->fullType);
        if ($field->null) {
            $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
        }
        if ($field->autoIncrement) {
            $type .= ' <i>' . $this->utils->trans->lang('Auto Increment') . '</i>';
        }
        if ($field->default !== '') {
            $type .= /*' ' . $this->utils->trans->lang('Default value') .*/ ' [<b>' .
                $this->utils->str->html($field->default) . '</b>]';
        }
        return $type;
    }
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
    public function getUnconvertedFieldValue(TableFieldEntity $field,
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
    public function readFileContent(array $file, string $key, bool $decompress): string
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
    public function getFileContents(string $key, bool $decompress = false)
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
     * Returns export format options
     *
     * @return array
     */
    public function dumpFormat(): array
    {
        return [
            'sql' => 'SQL',
            // 'csv' => 'CSV,',
            // 'csv;' => 'CSV;',
            // 'tsv' => 'TSV',
        ];
    }

    /**
     * Returns export output options
     *
     * @return array
     */
    public function dumpOutput(): array
    {
        $output = [
            'open' => $this->utils->trans->lang('open'),
            'save' => $this->utils->trans->lang('save'),
        ];
        if (function_exists('gzencode')) {
            $output['gzip'] = 'gzip';
        }
        return $output;
    }

    /**
     * Set the path of the file for webserver load
     *
     * @return string
     */
    public function importServerPath(): string
    {
        return 'adminer.sql';
    }
}
