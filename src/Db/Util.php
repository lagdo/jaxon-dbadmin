<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Input;
use Lagdo\DbAdmin\Driver\TranslatorInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Driver\UtilTrait;
use function intval;

class Util implements UtilInterface
{
    use UtilTrait;

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
     * Get escaped error message
     *
     * @return string
     */
    public function error(): string
    {
        return $this->html($this->driver->error());
    }

    /**
     * Check if the string is e-mail address
     *
     * @param mixed $email
     *
     * @return bool
     */
    public function isMail($email): bool
    {
        if (!\is_string($email)) {
            return false;
        }
        $atom = '[-a-z0-9!#$%&\'*+/=?^_`{|}~]'; // characters of local-name
        $domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component
        $pattern = "$atom+(\\.$atom+)*@($domain?\\.)+$domain";
        return \preg_match("(^$pattern(,\\s*$pattern)*\$)i", $email) > 0;
    }

    /**
     * Check if the string is URL address
     *
     * @param mixed $string
     *
     * @return bool
     */
    public function isUrl($string): bool
    {
        $domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN
        //! restrict path, query and fragment characters
        return \preg_match("~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string) > 0;
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
        return \preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~', $field->type) > 0;
    }

    /**
     * Get INI boolean value
     *
     * @param string $ini
     *
     * @return bool
     */
    public function iniBool(string $ini): bool
    {
        $value = \ini_get($ini);
        return (\preg_match('~^(on|true|yes)$~i', $value) || (int) $value); // boolean values set by php_value are strings
    }

    /**
     * Get INI bytes value
     *
     * @param string
     *
     * @return int
     */
    public function iniBytes(string $ini): int
    {
        $value = \ini_get($ini);
        $unit = \strtolower(\substr($value, -1)); // Get the last char
        $ival = \intval(\substr($value, 0, -1)); // Remove the last char
        switch ($unit) {
            case 'g': $value = $ival * 1024 * 1024 * 1024; break;
            case 'm': $value = $ival * 1024 * 1024; break;
            case 'k': $value = $ival * 1024; break;
        }
        return \intval($value);
    }

    /**
     * Escape column key used in where()
     *
     * @param string
     *
     * @return string
     */
    public function escapeKey(string $key): string
    {
        if (\preg_match('(^([\w(]+)(' .
            \str_replace('_', '.*', \preg_quote($this->driver->escapeId('_'))) . ')([ \w)]+)$)', $key, $match)) {
            //! columns looking like functions
            return $match[1] . $this->driver->escapeId($this->driver->unescapeId($match[2])) . $match[3]; //! SQL injection
        }
        return $this->driver->escapeId($key);
    }

    /**
     * Compute fields() from input edit data
     *
     * @return array
     */
    public function getFieldsFromEdit()
    {
        $fields = [];
        $values = $this->input->values;
        foreach ((array) $values['field_keys'] as $key => $value) {
            if ($value != '') {
                $value = $this->bracketEscape($value);
                $values['function'][$value] = $values['field_funs'][$key];
                $values['fields'][$value] = $values['field_vals'][$key];
            }
        }
        foreach ((array) $values['fields'] as $key => $value) {
            $name = $this->bracketEscape($key, 1); // 1 - back
            $fields[$name] = [
                'name' => $name,
                'privileges' => ['insert' => 1, 'update' => 1],
                'null' => 1,
                'autoIncrement' => false, // ($key == $this->driver->primaryIdName()),
            ];
        }
        return $fields;
    }

    /**
     * Create repeat pattern for preg
     *
     * @param string $pattern
     * @param int $length
     *
     * @return string
     */
    public function repeatPattern(string $pattern, int $length)
    {
        // fix for Compilation failed: number too big in {} quantifier
        // can create {0,0} which is OK
        return \str_repeat("$pattern{0,65535}", $length / 65535) . "$pattern{0," . ($length % 65535) . '}';
    }

    /**
     * Shorten UTF-8 string
     *
     * @param string $string
     * @param int $length
     * @param string $suffix
     *
     * @return string
     */
    public function shortenUtf8(string $string, int $length = 80, string $suffix = '')
    {
        if (!\preg_match('(^(' . $this->repeatPattern("[\t\r\n -\x{10FFFF}]", $length) . ')($)?)u', $string, $match)) {
            // ~s causes trash in $match[2] under some PHP versions, (.|\n) is slow
            \preg_match('(^(' . $this->repeatPattern("[\t\r\n -~]", $length) . ')($)?)', $string, $match);
        }
        return $this->html($match[1]) . $suffix . (isset($match[2]) ? '' : '<i>…</i>');
    }

    /**
     * Escape or unescape string to use inside form []
     *
     * @param string $idf
     * @param bool $back
     *
     * @return string
     */
    public function bracketEscape(string $idf, bool $back = false)
    {
        // escape brackets inside name='x[]'
        static $trans = [':' => ':1', ']' => ':2', '[' => ':3', '"' => ':4'];
        return \strtr($idf, ($back ? \array_flip($trans) : $trans));
    }

    /**
     * Find unique identifier of a row
     *
     * @param array $row
     * @param array $indexes Result of indexes()
     *
     * @return array
     */
    public function uniqueIds(array $row, array $indexes)
    {
        foreach ($indexes as $index) {
            if (\preg_match('~PRIMARY|UNIQUE~', $index->type)) {
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
    public function tableName(TableEntity $table)
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
    public function fieldName(TableFieldEntity $field, /** @scrutinizer ignore-unused */ int $order = 0)
    {
        return '<span title="' . $this->html($field->fullType) . '">' . $this->html($field->name) . '</span>';
    }

    /**
     * Returns export format options
     *
     * @return array
     */
    public function dumpFormat()
    {
        return ['sql' => 'SQL', 'csv' => 'CSV,', 'csv;' => 'CSV;', 'tsv' => 'TSV'];
    }

    /**
     * Returns export output options
     *
     * @return array
     */
    public function dumpOutput()
    {
        $output = ['text' => $this->trans->lang('open'), 'file' => $this->trans->lang('save')];
        if (\function_exists('gzencode')) {
            $output['gz'] = 'gzip';
        }
        return $output;
    }

    /**
     * Set the path of the file for webserver load
     *
     * @return string
     */
    public function importServerPath()
    {
        return 'adminer.sql';
    }

    /**
     * Export database structure
     *
     * @param string
     *
     * @return null prints data
     */
    // public function dumpDatabase($database) {
    // }

    /**
     * Print before edit form
     *
     * @param string $table
     * @param array $fields
     * @param mixed $row
     * @param bool $update
     *
     * @return null
     */
    // public function editRowPrint(string $table, array $fields, $row, bool $update)
    // {
    // }

    /**
     * Functions displayed in edit form
     *
     * @param TableFieldEntity $field Single field from fields()
     *
     * @return array
     */
    public function editFunctions(TableFieldEntity $field)
    {
        $update = isset($this->input->values['select']); // || $this->where([]);
        if ($field->autoIncrement && !$update) {
            return [$this->trans->lang('Auto Increment')];
        }

        $clauses = ($field->null ? 'NULL/' : '');
        foreach ($this->driver->editFunctions() as $key => $functions) {
            if (!$key || (!isset($this->input->values['call']) && $update)) { // relative functions
                foreach ($functions as $pattern => $value) {
                    if (!$pattern || \preg_match("~$pattern~", $field->type)) {
                        $clauses .= "/$value";
                    }
                }
            }
            if ($key && !\preg_match('~set|blob|bytea|raw|file|bool~', $field->type)) {
                $clauses .= '/SQL';
            }
        }
        return \explode('/', $clauses);
    }

    /**
     * Get hint for edit field
     *
     * @param string $table     Table name
     * @param TableFieldEntity $field   Single field from fields()
     * @param string $value
     *
     * @return string
     */
    // public function editHint(string $table, TableFieldEntity $field, string $value)
    // {
    //     return '';
    // }

    /**
     * Get a link to use in select table
     *
     * @param string $value     Raw value of the field
     * @param TableFieldEntity $field   Single field returned from fields()
     *
     * @return string|null
     */
    // private function selectLink(string $value, TableFieldEntity $field)
    // {
    // }

    /**
     * Print enum input field
     *
     * @param string $type Field type: "radio" or "checkbox"
     * @param string $attrs
     * @param TableFieldEntity $field
     * @param mixed $value int|string|array
     * @param string $empty
     *
     * @return null
     */
    // public function enum_input(string $type, string $attrs, TableFieldEntity $field, $value, string $empty = null)
    // {
    //     \preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
    //     $input = ($empty !== null ? "<label><input type='$type'$attrs value='$empty'" .
    //         ((is_array($value) ? in_array($empty, $value) : $value === 0) ? ' checked' : '') .
    //         '><i>' . $this->trans->lang('empty') . '</i></label>' : '');
    //     foreach ($matches[1] as $i => $val) {
    //         $val = stripcslashes(str_replace("''", "'", $val));
    //         $checked = (is_int($value) ? $value == $i+1 : (is_array($value) ? in_array($i+1, $value) : $value === $val));
    //         $input .= " <label><input type='$type'$attrs value='" . ($i+1) . "'" .
    //             ($checked ? ' checked' : '') . '>' . $this->util->html($val) . '</label>';
    //     }
    //     return $input;
    // }

    /**
     * Get options to display edit field
     *
     * @param bool $select
     * @param TableFieldEntity $field Single field from fields()
     * @param string $attrs Attributes to use inside the tag
     * @param mixed $value
     *
     * @return array
     */
    public function editInput(bool $select, TableFieldEntity $field, string $attrs, $value)
    {
        if ($field->type !== 'enum') {
            return [];
        }
        $inputs = [];
        if (($select)) {
            $inputs[] = "<label><input type='radio'$attrs value='-1' checked><i>" .
                $this->trans->lang('original') . '</i></label> ';
        }
        if (($field->null)) {
            $inputs[] = "<label><input type='radio'$attrs value=''" .
                ($value !== null || ($select) ? '' : ' checked') . '><i>NULL</i></label> ';
        }

        // From functions.inc.php (function enum_input())
        $empty = 0; // 0 - empty
        $type = 'radio';
        $inputs[] = "<label><input type='$type'$attrs value='$empty'" .
            ((\is_array($value) ? \in_array($empty, $value) : $value === 0) ? ' checked' : '') .
            '><i>' . $this->trans->lang('empty') . '</i></label>';

        \preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
        foreach ($matches[1] as $i => $val) {
            $val = \stripcslashes(\str_replace("''", "'", $val));
            $checked = (\is_int($value) ? $value == $i + 1 :
                (\is_array($value) ? \in_array($i+1, $value) : $value === $val));
            $inputs[] = "<label><input type='$type'$attrs value='" . ($i+1) . "'" .
                ($checked ? ' checked' : '') . '>' . $this->html($val) . '</label>';
        }

        return $inputs;
    }

    /**
     * Get file contents from $_FILES
     *
     * @param string $key
     * @param bool $decompress
     *
     * @return int|string
     */
    private function getFile(string $key, bool $decompress = false)
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
            if ($error) {
                return $error;
            }
            $name = $file['name'][$key];
            $tmpName = $file['tmp_name'][$key];
            $content = \file_get_contents($decompress && \preg_match('~\.gz$~', $name) ?
                "compress.zlib://$tmpName" : $tmpName); //! may not be reachable because of open_basedir
            if ($decompress) {
                $start = \substr($content, 0, 3);
                if (\function_exists('iconv') && \preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs)) {
                    // not ternary operator to save memory
                    $content = \iconv('utf-16', 'utf-8', $content);
                } elseif ($start == "\xEF\xBB\xBF") { // UTF-8 BOM
                    $content = \substr($content, 3);
                }
                $queries .= $content . "\n\n";
            } else {
                $queries .= $content;
            }
        }
        //! support SQL files not ending with semicolon
        return $queries;
    }

    /**
     * Filter length value including enums
     *
     * @param string $length
     *
     * @return string
     */
    public function processLength(string $length)
    {
        if (!$length) {
            return '';
        }
        $enumLength = $this->driver->enumLength();
        return (\preg_match("~^\\s*\\(?\\s*$enumLength(?:\\s*,\\s*$enumLength)*+\\s*\\)?\\s*\$~", $length) &&
            \preg_match_all("~$enumLength~", $length, $matches) ? '(' . \implode(',', $matches[0]) . ')' :
            \preg_replace('~^[0-9].*~', '(\0)', \preg_replace('~[^-0-9,+()[\]]~', '', $length))
        );
    }

    /**
     * Create SQL string from field type
     *
     * @param TableFieldEntity $field
     * @param string $collate
     *
     * @return string
     */
    private function processType(TableFieldEntity $field, string $collate = 'COLLATE')
    {
        $values = [
            'unsigned' => $field->unsigned,
            'collation' => $field->collation,
        ];
        return ' ' . $field->type . $this->processLength($field->length) .
            (\preg_match($this->driver->numberRegex(), $field->type) &&
            \in_array($values['unsigned'], $this->driver->unsigned()) ?
            " {$values['unsigned']}" : '') . (\preg_match('~char|text|enum|set~', $field->type) &&
            $values['collation'] ? " $collate " . $this->driver->quote($values['collation']) : '');
    }

    /**
     * Create SQL string from field
     *
     * @param TableFieldEntity $field Basic field information
     * @param TableFieldEntity $typeField Information about field type
     *
     * @return array
     */
    public function processField(TableFieldEntity $field, TableFieldEntity $typeField)
    {
        return [
            $this->driver->escapeId(trim($field->name)),
            $this->processType($typeField),
            ($field->null ? ' NULL' : ' NOT NULL'), // NULL for timestamp
            $this->driver->defaultValue($field),
            (\preg_match('~timestamp|datetime~', $field->type) && $field->onUpdate ?
                " ON UPDATE {$field->onUpdate}" : ''),
            ($this->driver->support('comment') && $field->comment != '' ?
                ' COMMENT ' . $this->driver->quote($field->comment) : ''),
            ($field->autoIncrement ? $this->driver->autoIncrement() : null),
        ];
    }

    /**
     * Process edit input field
     *
     * @param TableFieldEntity $field
     * @param array $inputs The user inputs
     *
     * @return mixed
     */
    public function processInput(TableFieldEntity $field, array $inputs)
    {
        $idf = $this->bracketEscape($field->name);
        $function = $inputs['function'][$idf] ?? '';
        $value = $inputs['fields'][$idf];
        if ($field->type == 'enum') {
            if ($value == -1) {
                return false;
            }
            if ($value == '') {
                return 'NULL';
            }
            return +$value;
        }
        if ($field->autoIncrement && $value == '') {
            return null;
        }
        if ($function == 'orig') {
            return (\preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) ?
                $this->driver->escapeId($field->name) : false);
        }
        if ($function == 'NULL') {
            return 'NULL';
        }
        if ($field->type == 'set') {
            return \array_sum((array) $value);
        }
        if ($function == 'json') {
            $value = \json_decode($value, true);
            if (!\is_array($value)) {
                return false; //! report errors
            }
            return $value;
        }
        if (\preg_match('~blob|bytea|raw|file~', $field->type) && $this->iniBool('file_uploads')) {
            $file = $this->getFile("fields-$idf");
            if (!\is_string($file)) {
                return false; //! report errors
            }
            return $this->driver->quoteBinary($file);
        }
        return $this->_processInput($field, $value, $function);
    }

    /**
     * Apply SQL function
     *
     * @param string $function
     * @param string $column escaped column identifier
     *
     * @return string
     */
    public function applySqlFunction(string $function, string $column)
    {
        return ($function ? ($function == 'unixepoch' ?
            "DATETIME($column, '$function')" : ($function == 'count distinct' ?
            'COUNT(DISTINCT ' : strtoupper("$function(")) . "$column)") : $column);
    }

    /**
     * Process columns box in select
     *
     * @return array
     */
    public function processSelectColumns()
    {
        $select = []; // select expressions, empty for *
        $group = []; // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
        foreach ((array) $this->input->values['columns'] as $key => $val) {
            if ($val['fun'] == 'count' ||
                ($val['col'] != '' && (!$val['fun'] ||
                \in_array($val['fun'], $this->driver->functions()) ||
                \in_array($val['fun'], $this->driver->grouping())))) {
                $select[$key] = $this->applySqlFunction(
                    $val['fun'],
                    ($val['col'] != '' ? $this->driver->escapeId($val['col']) : '*')
                );
                if (!in_array($val['fun'], $this->driver->grouping())) {
                    $group[] = $select[$key];
                }
            }
        }
        return [$select, $group];
    }

    /**
     * Process sent input
     *
     * @param TableFieldEntity $field Single field from fields()
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    private function _processInput(TableFieldEntity $field, string $value, string $function = '')
    {
        if ($function == 'SQL') {
            return $value; // SQL injection
        }
        $name = $field->name;
        $expression = $this->driver->quote($value);
        if (\preg_match('~^(now|getdate|uuid)$~', $function)) {
            $expression = "$function()";
        } elseif (\preg_match('~^current_(date|timestamp)$~', $function)) {
            $expression = $function;
        } elseif (\preg_match('~^([+-]|\|\|)$~', $function)) {
            $expression = $this->driver->escapeId($name) . " $function $expression";
        } elseif (\preg_match('~^[+-] interval$~', $function)) {
            $expression = $this->driver->escapeId($name) . " $function " .
                (\preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) ? $value : $expression);
        } elseif (\preg_match('~^(addtime|subtime|concat)$~', $function)) {
            $expression = "$function(" . $this->driver->escapeId($name) . ", $expression)";
        } elseif (\preg_match('~^(md5|sha1|password|encrypt)$~', $function)) {
            $expression = "$function($expression)";
        }
        return $this->driver->unconvertField($field, $expression);
    }

    /**
     * Process search box in select
     *
     * @param array $fields
     * @param array $indexes
     *
     * @return array
     */
    public function processSelectSearch(array $fields, array $indexes)
    {
        $expressions = [];
        foreach ($indexes as $i => $index) {
            if ($index->type == 'FULLTEXT' && $this->input->values['fulltext'][$i] != '') {
                $columns = \array_map(function ($column) {
                    return $this->driver->escapeId($column);
                }, $index->columns);
                $expressions[] = 'MATCH (' . \implode(', ', $columns) . ') AGAINST (' .
                    $this->driver->quote($this->input->values['fulltext'][$i]) .
                    (isset($this->input->values['boolean'][$i]) ? ' IN BOOLEAN MODE' : '') . ')';
            }
        }
        foreach ((array) $this->input->values['where'] as $key => $val) {
            if ("$val[col]$val[val]" != '' && in_array($val['op'], $this->driver->operators())) {
                $prefix = '';
                $cond = " $val[op]";
                if (\preg_match('~IN$~', $val['op'])) {
                    $in = $this->processLength($val['val']);
                    $cond .= ' ' . ($in != '' ? $in : '(NULL)');
                } elseif ($val['op'] == 'SQL') {
                    $cond = " $val[val]"; // SQL injection
                } elseif ($val['op'] == 'LIKE %%') {
                    $cond = ' LIKE ' . $this->_processInput($fields[$val['col']], "%$val[val]%");
                } elseif ($val['op'] == 'ILIKE %%') {
                    $cond = ' ILIKE ' . $this->_processInput($fields[$val['col']], "%$val[val]%");
                } elseif ($val['op'] == 'FIND_IN_SET') {
                    $prefix = "$val[op](" . $this->driver->quote($val['val']) . ', ';
                    $cond = ')';
                } elseif (!\preg_match('~NULL$~', $val['op'])) {
                    $cond .= ' ' . $this->_processInput($fields[$val['col']], $val['val']);
                }
                if ($val['col'] != '') {
                    $expressions[] = $prefix . $this->driver->convertSearch(
                        $this->driver->escapeId($val['col']),
                        $val,
                        $fields[$val['col']]
                    ) . $cond;
                } else {
                    // find anywhere
                    $cols = [];
                    foreach ($fields as $name => $field) {
                        if ((\preg_match('~^[-\d.' . (\preg_match('~IN$~', $val['op']) ? ',' : '') . ']+$~', $val['val']) ||
                            !\preg_match('~' . $this->driver->numberRegex() . '|bit~', $field->type)) &&
                            (!\preg_match("~[\x80-\xFF]~", $val['val']) || \preg_match('~char|text|enum|set~', $field->type)) &&
                            (!\preg_match('~date|timestamp~', $field->type) || \preg_match('~^\d+-\d+-\d+~', $val['val']))
                        ) {
                            $cols[] = $prefix . $this->driver->convertSearch($this->driver->escapeId($name), $val, $field) . $cond;
                        }
                    }
                    $expressions[] = ($cols ? '(' . \implode(' OR ', $cols) . ')' : '1 = 0');
                }
            }
        }
        return $expressions;
    }

    /**
     * Process order box in select
     *
     * @return array
     */
    public function processSelectOrder(): array
    {
        $expressions = [];
        foreach ((array) $this->input->values['order'] as $key => $val) {
            if ($val != '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                $expressions[] = (\preg_match($regexp, $val) ? $val : $this->driver->escapeId($val)) . //! MS SQL uses []
                    (isset($this->input->values['desc'][$key]) ? ' DESC' : '');
            }
        }
        return $expressions;
    }

    /**
     * Process limit box in select
     *
     * @return int
     */
    public function processSelectLimit(): int
    {
        return (isset($this->input->values['limit']) ? intval($this->input->values['limit']) : 50);
    }

    /**
     * Process length box in select
     *
     * @return int
     */
    public function processSelectLength(): int
    {
        return (isset($this->input->values['text_length']) ? intval($this->input->values['text_length']) : 100);
    }

    /**
     * Process extras in select form
     *
     * @param array $where AND conditions
     * @param array $foreignKeys
     *
     * @return bool
     */
    // public function processSelectEmail(array $where, array $foreignKeys)
    // {
    //     return false;
    // }

    /**
     * Value printed in select table
     *
     * @param mixed $value HTML-escaped value to print
     * @param string $link Link to foreign key
     * @param string $type Field type
     * @param mixed $original Original value before escaping
     *
     * @return string
     */
    private function _selectValue($value, string $link, string $type, $original): string
    {
        $clause = ($value === null ? '<i>NULL</i>' :
            (\preg_match('~char|binary|boolean~', $type) && !\preg_match('~var~', $type) ?
            "<code>$value</code>" : $value));
        if (\preg_match('~blob|bytea|raw|file~', $type) && !$this->isUtf8($value)) {
            $clause = '<i>' . $this->trans->lang('%d byte(s)', \strlen($original)) . '</i>';
        }
        if (\preg_match('~json~', $type)) {
            $clause = "<code class='jush-js'>$clause</code>";
        }
        return ($link ? "<a href='" . $this->html($link) . "'" .
            ($this->isUrl($link) ? $this->blankTarget() : '') . ">$clause</a>" : $clause);
    }

    /**
     * Format value to use in select
     *
     * @param mixed $value
     * @param string $link
     * @param TableFieldEntity $field
     * @param int|string|null $textLength
     *
     * @return string
     */
    public function selectValue($value, string $link, TableFieldEntity $field, $textLength): string
    {
        // if (\is_array($value)) {
        //     $expression = '';
        //     foreach ($value as $k => $v) {
        //         $expression .= '<tr>' . ($value != \array_values($value) ?
        //             '<th>' . $this->html($k) :
        //             '') . '<td>' . $this->selectValue($v, $link, $field, $textLength);
        //     }
        //     return "<table cellspacing='0'>$expression</table>";
        // }
        // if (!$link) {
        //     $link = $this->selectLink($value, $field);
        // }
        if ($link === '') {
            if ($this->isMail($value)) {
                $link = "mailto:$value";
            }
            elseif ($this->isUrl($value)) {
                $link = $value; // IE 11 and all modern browsers hide referrer
            }
        }
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
        return $this->_selectValue($expression, $link, $field->type, $value);
    }

    /**
     * Query printed in SQL command before execution
     *
     * @param string $query Query to be executed
     *
     * @return string
     */
    public function sqlCommandQuery(string $query)
    {
        return $this->shortenUtf8(trim($query), 1000);
    }
}
