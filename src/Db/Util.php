<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\Entity\IndexEntity;
use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Input;
use Lagdo\DbAdmin\Driver\TranslatorInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Driver\UtilTrait;

use function preg_match;
use function strlen;
use function array_sum;
use function is_string;

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
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getWhereCondition(array $value, array $fields): string
    {
        $op = $value['op'];
        $val = $value['val'];
        $col = $value['col'];
        if (preg_match('~IN$~', $op)) {
            $in = $this->processLength($val);
            return " $op " . ($in !== '' ? $in : '(NULL)');
        }
        if ($op === 'SQL') {
            return ' ' . $val; // SQL injection
        }
        if ($op === 'LIKE %%') {
            return ' LIKE ' . $this->getUnconvertedFieldValue($fields[$col], "%$val%");
        }
        if ($op === 'ILIKE %%') {
            return ' ILIKE ' . $this->getUnconvertedFieldValue($fields[$col], "%$val%");
        }
        if ($op === 'FIND_IN_SET') {
            return ')';
        }
        if (!preg_match('~NULL$~', $op)) {
            return " $op " . $this->getUnconvertedFieldValue($fields[$col], $val);
        }
        return " $op";
    }

    /**
     * @param TableFieldEntity $field
     * @param array $value
     *
     * @return bool
     */
    private function selectFieldIsValid(TableFieldEntity $field, array $value): bool
    {
        $op = $value['op'];
        $val = $value['val'];
        $in = preg_match('~IN$~', $op) ? ',' : '';
        return (preg_match('~^[-\d.' . $in . ']+$~', $val) ||
                !preg_match('~' . $this->driver->numberRegex() . '|bit~', $field->type)) &&
            (!preg_match("~[\x80-\xFF]~", $val) ||
                preg_match('~char|text|enum|set~', $field->type)) &&
            (!preg_match('~date|timestamp~', $field->type) ||
                preg_match('~^\d+-\d+-\d+~', $val));
    }

    /**
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getSelectExpression(array $value, array $fields): string
    {
        $op = $value['op'];
        $col = $value['col'];
        $prefix = '';
        if ($op === 'FIND_IN_SET') {
            $prefix = $op .'(' . $this->driver->quote($value['val']) . ', ';
        }
        $condition = $this->getWhereCondition($value, $fields);
        if ($col !== '') {
            return $prefix . $this->driver->convertSearch($this->driver->escapeId($col),
                    $value, $fields[$col]) . $condition;
        }
        // find anywhere
        $clauses = [];
        foreach ($fields as $name => $field) {
            if ($this->selectFieldIsValid($field, $value)) {
                $clauses[] = $prefix . $this->driver->convertSearch($this->driver->escapeId($name),
                        $value, $field) . $condition;
            }
        }
        if (empty($clauses)) {
            return '1 = 0';
        }
        return '(' . implode(' OR ', $clauses) . ')';
    }

    /**
     * @param IndexEntity $index
     * @param int $i
     *
     * @return string
     */
    private function getMatchExpression(IndexEntity $index, int $i): string
    {
        $columns = array_map(function ($column) {
            return $this->driver->escapeId($column);
        }, $index->columns);
        $match = $this->driver->quote($this->input->values['fulltext'][$i]);
        if (isset($this->input->values['boolean'][$i])) {
            $match .= ' IN BOOLEAN MODE';
        }
        return 'MATCH (' . implode(', ', $columns) . ') AGAINST (' . $match . ')';
    }

    /**
     * Process search box in select
     *
     * @param array $fields
     * @param array $indexes
     *
     * @return array
     */
    public function processSelectWhere(array $fields, array $indexes): array
    {
        $expressions = [];
        foreach ($indexes as $i => $index) {
            if ($index->type === 'FULLTEXT' && $this->input->values['fulltext'][$i] !== '') {
                $expressions[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array) $this->input->values['where'] as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->driver->operators())) {
                $expressions[] = $this->getSelectExpression($value, $fields);
            }
        }
        return $expressions;
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
        if (preg_match($this->driver->numberRegex(), $field->type) &&
            in_array($field->unsigned, $this->driver->unsigned())) {
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
        $idf = $this->bracketEscape($field->name);
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
        $idf = $this->bracketEscape($field->name);
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
