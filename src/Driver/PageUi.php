<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function file_get_contents;
use function function_exists;
use function iconv;
use function implode;
use function max;
use function preg_match;
use function strlen;
use function strtoupper;
use function substr;

class PageUi
{
    /**
     * @param Utils $utils
     */
    public function __construct(private Utils $utils)
    {}

    /**
     * @return Utils
     */
    protected function utils(): Utils
    {
        return $this->utils;
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
     * Table caption used in navigation and headings
     *
     * @param TableDto $table
     *
     * @return string
     */
    public function tableName(TableDto $table): string
    {
        return $this->utils()->html($table->name);
    }

    /**
     * Field caption used in select and edit
     *
     * @param ColumnDto $column Single column returned from columns()
     *
     * @return string
     */
    public function columnName(ColumnDto $column): string
    {
        $fullType = $this->utils()->html($column->fullType);
        $name = $this->utils()->html($column->name);
        return "<span title=\"$fullType\">$name</span>";
    }

    /**
     * Apply SQL function
     *
     * @param string $function
     * @param string $columnName escaped column identifier
     *
     * @return string
     */
    public function applySqlFunction(string $function, string $columnName): string
    {
        return match(true) {
            !$function => $columnName,
            $function === 'unixepoch' => "DATETIME($columnName, '$function')",
            $function === 'count distinct' => "COUNT(DISTINCT $columnName)",
            default => strtoupper($function) . "($columnName)",
        };
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
    private function getSelectColumnValue($value, string $type, $original): string
    {
        return match(true) {
            $value === null => '<i>NULL</i>',
            preg_match('~char|binary|boolean~', $type) > 0 &&
                !preg_match('~var~', $type) => "<code>$value</code>",
            preg_match('~blob|bytea|raw|file~', $type) > 0 &&
                !$this->utils()->str->isUtf8($value) => '<i>' .
                    $this->utils()->lang('%d byte(s)', strlen($original)) . '</i>',
            preg_match('~json~', $type) > 0 => "<code>$value</code>",
            $this->utils()->isMail($value) => '<a href="' .
                $this->utils()->html("mailto:$value") . '">' . $value . '</a>',
            // IE 11 and all modern browsers hide referrer
            $this->utils()->isUrl($value) => '<a href="' . $this->utils()->html($value) .
                '"' . $this->blankTarget() . '>' . $value . '</a>',
            default => $value,
        };
    }

    /**
     * Format value to use in select
     *
     * @param ColumnDto $column
     * @param int $textLength
     * @param mixed $value
     *
     * @return string
     */
    public function selectValue(ColumnDto $column, int $textLength, mixed $value): string
    {
        // if (\is_array($value)) {
        //     $expression = '';
        //     foreach ($value as $k => $v) {
        //         $expression .= '<tr>' . ($value != \array_values($value) ?
        //             '<th>' . $this->utils()->html($k) :
        //             '') . '<td>' . $this->selectValue($column, $v, $textLength);
        //     }
        //     return "<table cellspacing='0'>$expression</table>";
        // }
        // if (!$link) {
        //     $link = $this->selectLink($value, $column);
        // }

        $expression = $value;
        if (!empty($expression)) {
            $expression = match(true) {
                // htmlspecialchars of binary data returns an empty string
                !$this->utils()->str->isUtf8($expression) => "\0",
                // usage of LEFT() would reduce traffic but complicate query -
                // expected average speedup: .001 s VS .01 s on local network
                $textLength !== 0 && $this->utils()->isShortable($column) =>
                    $this->utils()->str->shortenUtf8($expression, max(0, +$textLength)),
                default => $this->utils()->html($expression),
            };
        }

        return $this->getSelectColumnValue($expression, $column->type, $value);
    }

    /**
     * @param ColumnDto $column
     * @param int $textLength
     * @param mixed $value
     *
     * @return array
     */
    public function getColumnValue(ColumnDto $column, int $textLength, mixed $value): array
    {
        /*if ($value != "" && (!isset($email_columns[$key]) || $email_columns[$key] != "")) {
            //! filled e-mails can be contained on other pages
            $email_columns[$key] = ($this->isMail($value) ? $names[$key] : "");
        }*/
        return [
            // 'id',
            'text' => preg_match('~text|lob~', $column->type),
            'value' => $this->selectValue($column, $textLength, $value),
            // 'editable' => false,
        ];
    }

    /**
     * @param ColumnDto $column
     * @param string $tableCollation
     *
     * @return string
     */
    public function getColumnType(ColumnDto $column, string $tableCollation = ''): string
    {
        $type = $this->utils()->html($column->fullType);
        $collation = $this->utils()->html($column->collation);
        if ($collation !== '' && $tableCollation !== '' && $collation != $tableCollation) {
            $type .= " $collation";
        }
        $types = ["<span>$type</span>"];
        if ($column->nullable) {
            $types[] = '<i>nullable</i>'; // ' <i>NULL</i>';
        }
        if ($column->autoIncrement) {
            $types[] = '<i>' . $this->utils()->lang('Auto Increment') . '</i>';
        }
        if ($column->hasDefault()) {
            $types[] = '[<b>' . $this->utils()->html($column->default) . '</b>]';
        }

        return implode(' ', $types);
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
        //! may not be reachable because of open_basedir
        $content = file_get_contents($decompress && preg_match('~\.gz$~', $name) ?
            "compress.zlib://$tmpName" : $tmpName);
        $start = substr($content, 0, 3);

        return match(true) {
            !$decompress => $content,
            preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs) &&
                function_exists('iconv') => iconv('utf-16', 'utf-8', $content) . "\n\n",
            // UTF-8 BOM
            $start === "\xEF\xBB\xBF" => substr($content, 3) . "\n\n",
            default => $content,
        };
    }

    /**
     * Get file contents from $_FILES
     *
     * @param string $key
     * @param bool $decompress
     *
     * @return string|null
     */
    public function getFileContents(string $key, bool $decompress = false): string|null
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
            'open' => $this->utils()->lang('open'),
            'save' => $this->utils()->lang('save'),
        ];

        return !function_exists('gzencode') ? $output : [
            ...$output,
            'gzip' => 'gzip',
        ];
    }

    /**
     * Set the path of the file for webserver load
     *
     * @return string
     */
    public function importServerPath(): string
    {
        return 'dbadmin.sql';
    }
}
