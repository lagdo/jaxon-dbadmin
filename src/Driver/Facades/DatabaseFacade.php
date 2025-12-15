<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use function array_filter;
use function array_intersect;
use function array_values;
use function is_array;

/**
 * Facade to database functions
 */
class DatabaseFacade extends AbstractFacade
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
     * The constructor
     *
     * @param AbstractFacade $dbFacade
     * @param array $options    The server config options
     */
    public function __construct(AbstractFacade $dbFacade, array $options)
    {
        parent::__construct($dbFacade);
        // Set the user schemas, if defined.
        if (is_array(($userSchemas = $options['access']['schemas'] ?? null))) {
            $this->userSchemas = $userSchemas;
        }
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
            $this->finalSchemas = $this->driver->schemas();
            if ($this->userSchemas !== null) {
                // Only keep schemas that appear in the config.
                $this->finalSchemas = array_values(array_intersect($this->finalSchemas, $this->userSchemas));
            }
        }
        return $schemaAccess ? $this->finalSchemas : array_filter($this->finalSchemas,
            fn($schema) => !$this->driver->isSystemSchema($schema));
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
        if ($this->driver->support("scheme")) {
            $schemas = $this->schemas($schemaAccess);
        }
        // $tables_list = $this->driver->tables();

        // $tables = [];
        // foreach($tableStatus as $table)
        // {
        //     $tables[] = $this->utils->str->html($table);
        // }

        return \compact('schemas'/*, 'tables'*/);
    }

    /**
     * Get the tables from a database server
     *
     * @return array
     */
    public function getTables()
    {
        $headers = [
            $this->utils->trans->lang('Table'),
            $this->utils->trans->lang('Engine'),
            $this->utils->trans->lang('Collation'),
            // $this->utils->trans->lang('Data Length'),
            // $this->utils->trans->lang('Index Length'),
            // $this->utils->trans->lang('Data Free'),
            // $this->utils->trans->lang('Auto Increment'),
            // $this->utils->trans->lang('Rows'),
            $this->utils->trans->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->driver->tableStatuses(true); // Tables details
        $tableStatus = $this->driver->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if (!$this->driver->isView($status)) {
                $details[] = [
                    'name' => $this->page->tableName($status),
                    'engine' => $status->engine,
                    'collation' => '',
                    'comment' => $status->comment,
                ];
            }
        }

        return \compact('headers', 'details');
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
            $this->utils->trans->lang('View'),
            $this->utils->trans->lang('Engine'),
            // $this->utils->trans->lang('Data Length'),
            // $this->utils->trans->lang('Index Length'),
            // $this->utils->trans->lang('Data Free'),
            // $this->utils->trans->lang('Auto Increment'),
            // $this->utils->trans->lang('Rows'),
            $this->utils->trans->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->driver->tableStatuses(true); // Tables details
        $tableStatus = $this->driver->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if ($this->driver->isView($status)) {
                $details[] = [
                    'name' => $this->page->tableName($status),
                    'engine' => $status->engine,
                    'comment' => $status->comment,
                ];
            }
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getRoutines()
    {
        $headers = [
            $this->utils->trans->lang('Name'),
            $this->utils->trans->lang('Type'),
            $this->utils->trans->lang('Return type'),
        ];

        // From db.inc.php
        $routines = $this->driver->routines();
        $details = [];
        foreach ($routines as $routine) {
            // not computed on the pages to be able to print the header first
            // $name = ($routine["SPECIFIC_NAME"] == $routine["ROUTINE_NAME"] ?
            //     "" : "&name=" . urlencode($routine["ROUTINE_NAME"]));

            $details[] = [
                'name' => $this->utils->str->html($routine->name),
                'type' => $this->utils->str->html($routine->type),
                'returnType' => $this->utils->str->html($routine->dtd),
                // 'alter' => $this->utils->trans->lang('Alter'),
            ];
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getSequences()
    {
        $headers = [
            $this->utils->trans->lang('Name'),
        ];

        $details = [];
        foreach ($this->driver->sequences() as $sequence) {
            $details[] = [
                'name' => $this->utils->str->html($sequence),
            ];
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getUserTypes()
    {
        $headers = [
            $this->utils->trans->lang('Name'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->driver->userTypes(false) as $userType) {
            $details[] = [
                'name' => $this->utils->str->html($userType->name),
            ];
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getEvents()
    {
        $headers = [
            $this->utils->trans->lang('Name'),
            $this->utils->trans->lang('Schedule'),
            $this->utils->trans->lang('Start'),
            // $this->utils->trans->lang('End'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->driver->events() as $event) {
            $detail = [
                'name' => $this->utils->str->html($event["Name"]),
            ];
            if (($event["Execute at"])) {
                $detail['schedule'] = $this->utils->trans->lang('At given time');
                $detail['start'] = $event["Execute at"];
            // $detail['end'] = '';
            } else {
                $detail['schedule'] = $this->utils->trans->lang('Every') . " " .
                    $event["Interval value"] . " " . $event["Interval field"];
                $detail['start'] = $event["Starts"];
                // $detail['end'] = '';
            }
            $details[] = $detail;
        }

        return \compact('headers', 'details');
    }
}
