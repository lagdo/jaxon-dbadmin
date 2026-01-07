<?php

namespace Lagdo\DbAdmin\Db\Page\Ddl;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function str_replace;

trait ForeignKeyTrait
{
    /**
     * @var array
     */
    protected $referencableFields = null;

    /**
     * @var array<string,string>
     */
    protected $foreignKeys = [];

    /**
     * @param string $table
     *
     * @return array<TableFieldEntity>
     */
    private function getReferencableFields(string $table): array
    {
        // From editing.inc.php, function referencable_primary()
        $fields = []; // table_name => field
        foreach ($this->driver->tableStatuses(true) as $tableName => $tableStatus) {
            if ($tableName != $table && $this->driver->supportForeignKeys($tableStatus)) {
                $tableFields = $this->driver->fields($tableName);
                foreach ($tableFields as $field) {
                    if ($field->primary) {
                        if (isset($fields[$tableName])) { // multi column primary key
                            unset($fields[$tableName]);
                            break;
                        }
                        $fields[$tableName] = $field;
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * @param string $table
     *
     * @return array<TableFieldEntity>
     */
    private function referencableFields(string $table = ''): array
    {
        return $this->referencableFields ??= $this->getReferencableFields($table);
    }

    /**
     * Get foreign keys
     *
     * @param string $table     The table name
     *
     * @return void
     */
    protected function getForeignKeys(string $table = ''): void
    {
        $this->foreignKeys = [];
        foreach ($this->referencableFields($table) as $tableName => $field) {
            $name = str_replace("`", "``", $tableName) . "`" .
                str_replace("`", "``", $field->name);
            // not escapeId() - used in JS
            $this->foreignKeys[$name] = $tableName;
        }
    }
}
