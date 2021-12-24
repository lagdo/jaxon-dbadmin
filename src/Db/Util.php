<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Input;
use Lagdo\DbAdmin\Driver\TranslatorInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Driver\UtilTrait;

use function preg_match;
use function strlen;

class Util implements UtilInterface
{
    use UtilTrait;
    use Traits\UtilTrait;
    use Traits\SelectUtilTrait;
    use Traits\QueryInputTrait;
    use Traits\DumpUtilTrait;

    /**
     * The constructor
     *
     * @param TranslatorInterface $trans
     * @param Input $input
     */
    public function __construct(TranslatorInterface $trans, Input $input)
    {
        $this->trans = $trans;
        $this->input = $input;
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return '<a href="https://www.adminer.org/"' . $this->blankTarget() . ' id="h1">Adminer</a>';
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
     * Find unique identifier of a row
     *
     * @param array $row
     * @param array $indexes Result of indexes()
     *
     * @return array
     */
    public function uniqueIds(array $row, array $indexes): array
    {
        foreach ($indexes as $index) {
            if (preg_match('~PRIMARY|UNIQUE~', $index->type)) {
                $ids = [];
                foreach ($index->columns as $key) {
                    if (!isset($row[$key])) { // NULL is ambiguous
                        continue 2;
                    }
                    $ids[$key] = $row[$key];
                }
                return $ids;
            }
        }
        return [];
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
        return $this->html($table->name);
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
        return '<span title="' . $this->html($field->fullType) . '">' . $this->html($field->name) . '</span>';
    }

    /**
     * @param TableFieldEntity $field
     * @param array $values First entries
     * @param bool $update
     *
     * @return string[]
     */
    private function getEditFunctionNames(TableFieldEntity $field, array $values, bool $update): array
    {
        $names = $values;
        foreach ($this->driver->editFunctions() as $key => $functions) {
            if (!$key || (!isset($this->input->values['call']) && $update)) { // relative functions
                foreach ($functions as $pattern => $value) {
                    if (!$pattern || preg_match("~$pattern~", $field->type)) {
                        $names[] = $value;
                    }
                }
            }
            if ($key && !preg_match('~set|blob|bytea|raw|file|bool~', $field->type)) {
                $names[] = 'SQL';
            }
        }
        return $names;
    }

    /**
     * Functions displayed in edit form
     *
     * @param TableFieldEntity $field Single field from fields()
     *
     * @return array
     */
    public function editFunctions(TableFieldEntity $field): array
    {
        $update = isset($this->input->values['select']); // || $this->where([]);
        if ($field->autoIncrement && !$update) {
            return [$this->trans->lang('Auto Increment')];
        }

        $names = ($field->null ? ['NULL', ''] : ['']);
        return $this->getEditFunctionNames($field, $names, $update);
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
        if ($value === null) {
            return '<i>NULL</i>';
        }
        if (preg_match('~char|binary|boolean~', $type) && !preg_match('~var~', $type)) {
            return "<code>$value</code>";
        }
        if (preg_match('~blob|bytea|raw|file~', $type) && !$this->isUtf8($value)) {
            return '<i>' . $this->trans->lang('%d byte(s)', strlen($original)) . '</i>';
        }
        if (preg_match('~json~', $type)) {
            return "<code>$value</code>";
        }
        if ($this->isMail($value)) {
            return '<a href="' . $this->html("mailto:$value") . '">' . $value . '</a>';
        }
        elseif ($this->isUrl($value)) {
            // IE 11 and all modern browsers hide referrer
            return '<a href="' . $this->html($value) . '"' . $this->blankTarget() . '>' . $value . '</a>';
        }
        return $value;
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
        //             '<th>' . $this->html($k) :
        //             '') . '<td>' . $this->selectValue($field, $v, $textLength);
        //     }
        //     return "<table cellspacing='0'>$expression</table>";
        // }
        // if (!$link) {
        //     $link = $this->selectLink($value, $field);
        // }
        $expression = $value;
        if (!empty($expression)) {
            if (!$this->isUtf8($expression)) {
                $expression = "\0"; // htmlspecialchars of binary data returns an empty string
            } elseif ($textLength != '' && $this->isShortable($field)) {
                // usage of LEFT() would reduce traffic but complicate query -
                // expected average speedup: .001 s VS .01 s on local network
                $expression = $this->shortenUtf8($expression, \max(0, +$textLength));
            } else {
                $expression = $this->html($expression);
            }
        }
        return $this->getSelectFieldValue($expression, $field->type, $value);
    }

    /**
     * Create SQL string from field type
     *
     * @param TableFieldEntity $field
     * @param string $collate
     *
     * @return string
     */
    private function processType(TableFieldEntity $field, string $collate = 'COLLATE'): string
    {
        $collation = '';
        if (preg_match('~char|text|enum|set~', $field->type) && $field->collation) {
            $collation = " $collate " . $this->driver->quote($field->collation);
        }
        $sign = '';
        if (preg_match($this->driver->numberRegex(), $field->type) && in_array($field->unsigned, $this->driver->unsigned())) {
            $sign = ' ' . $field->unsigned;
        }
        return ' ' . $field->type . $this->processLength($field->length) . $sign . $collation;
    }

    /**
     * Create SQL string from field
     *
     * @param TableFieldEntity $field Basic field information
     * @param TableFieldEntity $typeField Information about field type
     *
     * @return array
     */
    public function processField(TableFieldEntity $field, TableFieldEntity $typeField): array
    {
        $onUpdate = '';
        if (preg_match('~timestamp|datetime~', $field->type) && $field->onUpdate) {
            $onUpdate = ' ON UPDATE ' . $field->onUpdate;
        }
        $comment = '';
        if ($this->driver->support('comment') && $field->comment !== '') {
            $comment = ' COMMENT ' . $this->driver->quote($field->comment);
        }
        $null = $field->null ? ' NULL' : ' NOT NULL'; // NULL for timestamp
        $autoIncrement = $field->autoIncrement ? $this->driver->autoIncrement() : null;
        return [$this->driver->escapeId(trim($field->name)), $this->processType($typeField),
            $null, $this->driver->defaultValue($field), $onUpdate, $comment, $autoIncrement];
    }
}
