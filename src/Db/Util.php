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
    use Traits\UtilTrait;
    use Traits\SelectQueryTrait;
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
