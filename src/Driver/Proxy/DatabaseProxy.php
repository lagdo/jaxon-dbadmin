<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\TableFieldDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DatabaseHeader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DatabaseContent;

use function array_filter;
use function array_intersect;
use function array_map;
use function array_values;
use function is_array;

/**
 * Proxy to database functions
 */
class DatabaseProxy extends AbstractDriverProxy
{
    /**
     * The final schema list
     *
     * @var array|null
     */
    protected $finalSchemas = null;

    /**
     * The schemas the user has access to
     *
     * @var array|null
     */
    protected $userSchemas = null;

    /**
     * @var DatabaseHeader|null
     */
    private DatabaseHeader|null $databaseHeader = null;

    /**
     * @var DatabaseContent|null
     */
    private DatabaseContent|null $databaseContent = null;

    /**
     * @return DatabaseHeader
     */
    private function header(): DatabaseHeader
    {
        return $this->databaseHeader ??= new DatabaseHeader($this->helper());
    }

    /**
     * @return DatabaseContent
     */
    private function content(): DatabaseContent
    {
        return $this->databaseContent ??= new DatabaseContent($this->helper());
    }

    /**
     * @param array $options    The server config options
     *
     * @return static
     */
    public function setOptions(array $options): static
    {
        // Set the user schemas, if defined.
        if (is_array(($userSchemas = $options['access']['schemas'] ?? null))) {
            $this->userSchemas = $userSchemas;
        }
        return $this;
    }

    /**
     * Get the schemas from the connected database
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    protected function schemas(bool $schemaAccess): array
    {
        // Get the schema lists
        if ($this->finalSchemas === null) {
            $this->finalSchemas = $this->engine()->schemas();
            if ($this->userSchemas !== null) {
                // Only keep schemas that appear in the config.
                $this->finalSchemas = array_values(array_intersect($this->finalSchemas, $this->userSchemas));
            }
        }

        return $schemaAccess ? $this->finalSchemas :
            array_filter($this->finalSchemas, $this->engine()->isUserSchema(...));
    }

    /**
     * Connect to a database server
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    public function getDatabaseInfo(bool $schemaAccess): array
    {
        // From db.inc.php
        $schemas = $this->engine()->support("scheme") ? $this->schemas($schemaAccess) : null;

        // $tables_list = $this->engine()->tables();
        // $tables = [];
        // foreach($tableStatus as $table)
        // {
        //     $tables[] = $this->utils()->html($table);
        // }

        return [
            'schemas' => $schemas,
            // 'tables' => $tables,
        ];
    }

    /**
     * Get the tables from a database server
     *
     * @return array
     */
    public function getTables(): array
    {
        // From db.inc.php
        // $tableStatus = $this->engine()->tableStatuses(true); // Tables details
        $tables = array_filter($this->engine()->tableStatuses(), $this->engine()->isTable(...));

        return [
            'headers' => $this->header()->tables(),
            'details' => $this->content()->tables($tables),
        ];
    }

    /**
     * Get the views from a database server
     *
     * @return array
     */
    public function getViews(): array
    {
        // From db.inc.php
        // $tableStatus = $this->engine()->tableStatuses(true); // Tables details
        $views = array_filter($this->engine()->tableStatuses(), $this->engine()->isView(...));

        return [
            'headers' => $this->header()->views(),
            'details' => $this->content()->views($views),
        ];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getRoutines(): array
    {
        // From db.inc.php
        $routines =$this->engine()->routines();

        return [
            'headers' => $this->header()->routines(),
            'details' => $this->content()->routines($routines),
        ];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getSequences(): array
    {
        $sequences = $this->engine()->sequences();

        return [
            'headers' => $this->header()->sequences(),
            'details' => $this->content()->sequences($sequences),
        ];
    }

    /**
     * Get the uer types from a given database
     *
     * @return array
     */
    public function getUserTypes(): array
    {
        // From db.inc.php
        $userTypes = $this->engine()->userTypes(false);

        return [
            'headers' => $this->header()->userTypes(),
            'details' => $this->content()->userTypes($userTypes),
        ];
    }

    /**
     * Get the events from a given database
     *
     * @return array
     */
    public function getEvents(): array
    {
        // From db.inc.php
        $events = $this->engine()->events();

        return [
            'headers' => $this->header()->events(),
            'details' => $this->content()->events($events),
        ];
    }

    /**
     * Get all the columns of all the tables in the database.
     *
     * @return array
     */
    public function getSchemaColumns(): array
    {
        $fieldCallback = fn(TableFieldDto $field) => ['name' => $field->name];
        $tables = array_map(fn(string $table) => [
            'name' => $table,
            'columns' => array_values(array_map($fieldCallback, $this->engine()->fields($table))),
        ], $this->engine()->tableNames());

        return ['tables' => $tables];
    }
}
