<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function function_exists;
use function is_string;
use function max;
use function preg_match;
use function strlen;

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
     * @param mixed $value
     * @param int|string|null $textLength
     *
     * @return string
     */
    public function selectValue(TableFieldEntity $field, $value, $textLength): string
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
