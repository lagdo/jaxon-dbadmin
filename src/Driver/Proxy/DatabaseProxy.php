<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableFieldDto;

use function array_filter;
use function array_map;
use function array_intersect;
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
    protected function schemas(bool $schemaAccess)
    {
        // Get the schema lists
        if ($this->finalSchemas === null) {
            $this->finalSchemas = $this->engine()->schemas();
            if ($this->userSchemas !== null) {
                // Only keep schemas that appear in the config.
                $this->finalSchemas = array_values(array_intersect($this->finalSchemas, $this->userSchemas));
            }
        }
        return $schemaAccess ? $this->finalSchemas : array_filter($this->finalSchemas,
            fn($schema) => !$this->engine()->isSystemSchema($schema));
    }

    /**
     * Connect to a database server
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    public function getDatabaseInfo(bool $schemaAccess)
    {
        // From db.inc.php
        $schemas = null;
        if ($this->engine()->support("scheme")) {
            $schemas = $this->schemas($schemaAccess);
        }

        // $tables_list = $this->engine()->tables();
        // $tables = [];
        // foreach($tableStatus as $table)
        // {
        //     $tables[] = $this->utils()->html($table);
        // }

        return ['schemas' => $schemas, /*'tables' => $tables*/];
    }

    /**
     * Get the tables from a database server
     *
     * @return array
     */
    public function getTables()
    {
        $headers = [
            $this->utils()->lang('Table'),
            $this->utils()->lang('Engine'),
            $this->utils()->lang('Collation'),
            // $this->utils()->lang('Data Length'),
            // $this->utils()->lang('Index Length'),
            // $this->utils()->lang('Data Free'),
            // $this->utils()->lang('Auto Increment'),
            // $this->utils()->lang('Rows'),
            $this->utils()->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->engine()->tableStatuses(true); // Tables details
        $tableStatus = $this->engine()->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if (!$this->engine()->isView($status)) {
                $details[] = [
                    'name' => $this->pageUi()->tableName($status),
                    'engine' => $status->engine,
                    'collation' => '',
                    'comment' => $status->comment,
                ];
            }
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the views from a database server
     * Almost the same as getTables()
     *
     * @return array
     */
    public function getViews()
    {
        $headers = [
            $this->utils()->lang('View'),
            $this->utils()->lang('Engine'),
            // $this->utils()->lang('Data Length'),
            // $this->utils()->lang('Index Length'),
            // $this->utils()->lang('Data Free'),
            // $this->utils()->lang('Auto Increment'),
            // $this->utils()->lang('Rows'),
            $this->utils()->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->engine()->tableStatuses(true); // Tables details
        $tableStatus = $this->engine()->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if ($this->engine()->isView($status)) {
                $details[] = [
                    'name' => $this->pageUi()->tableName($status),
                    'engine' => $status->engine,
                    'comment' => $status->comment,
                ];
            }
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getRoutines()
    {
        $headers = [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Return type'),
        ];

        // From db.inc.php
        $routines = $this->engine()->routines();
        $details = [];
        foreach ($routines as $routine) {
            // not computed on the pages to be able to print the header first
            // $name = ($routine["SPECIFIC_NAME"] == $routine["ROUTINE_NAME"] ?
            //     "" : "&name=" . urlencode($routine["ROUTINE_NAME"]));

            $details[] = [
                'name' => $this->utils()->html($routine->name),
                'type' => $this->utils()->html($routine->type),
                'returnType' => $this->utils()->html($routine->dtd),
                // 'alter' => $this->utils()->lang('Alter'),
            ];
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getSequences()
    {
        $headers = [
            $this->utils()->lang('Name'),
        ];

        $details = [];
        foreach ($this->engine()->sequences() as $sequence) {
            $details[] = [
                'name' => $this->utils()->html($sequence),
            ];
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getUserTypes()
    {
        $headers = [
            $this->utils()->lang('Name'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->engine()->userTypes(false) as $userType) {
            $details[] = [
                'name' => $this->utils()->html($userType->name),
            ];
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getEvents()
    {
        $headers = [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Schedule'),
            $this->utils()->lang('Start'),
            // $this->utils()->lang('End'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->engine()->events() as $event) {
            $detail = [
                'name' => $this->utils()->html($event["Name"]),
            ];
            if (($event["Execute at"])) {
                $detail['schedule'] = $this->utils()->lang('At given time');
                $detail['start'] = $event["Execute at"];
            // $detail['end'] = '';
            } else {
                $detail['schedule'] = $this->utils()->lang('Every') . " " .
                    $event["Interval value"] . " " . $event["Interval field"];
                $detail['start'] = $event["Starts"];
                // $detail['end'] = '';
            }
            $details[] = $detail;
        }

        return ['headers' => $headers, 'details' => $details];
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
