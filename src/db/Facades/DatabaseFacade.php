<?php

namespace Lagdo\DbAdmin\Db\Facades;

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
     * @return array
     */
    protected function schemas()
    {
        // Get the schema lists
        if ($this->finalSchemas === null) {
            $this->finalSchemas = $this->driver->schemas();
            if ($this->userSchemas !== null) {
                // Only keep schemas that appear in the config.
                $this->finalSchemas = array_values(array_intersect($this->finalSchemas, $this->userSchemas));
            }
        }
        return $this->finalSchemas;
    }

    /**
     * Connect to a database server
     *
     * @return array
     */
    public function getDatabaseInfo()
    {
        // From db.inc.php
        $schemas = null;
        if ($this->driver->support("scheme")) {
            $schemas = $this->schemas();
        }
        // $tables_list = $this->driver->tables();

        // $tables = [];
        // foreach($tableStatus as $table)
        // {
        //     $tables[] = $this->util->html($table);
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
            $this->trans->lang('Table'),
            $this->trans->lang('Engine'),
            $this->trans->lang('Collation'),
            // $this->trans->lang('Data Length'),
            // $this->trans->lang('Index Length'),
            // $this->trans->lang('Data Free'),
            // $this->trans->lang('Auto Increment'),
            // $this->trans->lang('Rows'),
            $this->trans->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->driver->tableStatuses(true); // Tables details
        $tableStatus = $this->driver->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if (!$this->driver->isView($status)) {
                $details[] = [
                    'name' => $this->util->tableName($status),
                    'engine' => $status->engine,
                    'collation' => '',
                    'comment' => $status->comment,
                ];
            }
        }

        $select = $this->trans->lang('Select');
        return \compact('headers', 'details', 'select');
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
            $this->trans->lang('View'),
            $this->trans->lang('Engine'),
            // $this->trans->lang('Data Length'),
            // $this->trans->lang('Index Length'),
            // $this->trans->lang('Data Free'),
            // $this->trans->lang('Auto Increment'),
            // $this->trans->lang('Rows'),
            $this->trans->lang('Comment'),
        ];

        // From db.inc.php
        // $tableStatus = $this->driver->tableStatuses(true); // Tables details
        $tableStatus = $this->driver->tableStatuses(); // Tables details

        $details = [];
        foreach ($tableStatus as $table => $status) {
            if ($this->driver->isView($status)) {
                $details[] = [
                    'name' => $this->util->tableName($status),
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
            $this->trans->lang('Name'),
            $this->trans->lang('Type'),
            $this->trans->lang('Return type'),
        ];

        // From db.inc.php
        $routines = $this->driver->routines();
        $details = [];
        foreach ($routines as $routine) {
            // not computed on the pages to be able to print the header first
            // $name = ($routine["SPECIFIC_NAME"] == $routine["ROUTINE_NAME"] ?
            //     "" : "&name=" . urlencode($routine["ROUTINE_NAME"]));

            $details[] = [
                'name' => $this->util->html($routine->name),
                'type' => $this->util->html($routine->type),
                'returnType' => $this->util->html($routine->dtd),
                // 'alter' => $this->trans->lang('Alter'),
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
            $this->trans->lang('Name'),
        ];

        $details = [];
        foreach ($this->driver->sequences() as $sequence) {
            $details[] = [
                'name' => $this->util->html($sequence),
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
            $this->trans->lang('Name'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->driver->userTypes() as $userType) {
            $details[] = [
                'name' => $this->util->html($userType),
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
            $this->trans->lang('Name'),
            $this->trans->lang('Schedule'),
            $this->trans->lang('Start'),
            // $this->trans->lang('End'),
        ];

        // From db.inc.php
        $details = [];
        foreach ($this->driver->events() as $event) {
            $detail = [
                'name' => $this->util->html($event["Name"]),
            ];
            if (($event["Execute at"])) {
                $detail['schedule'] = $this->trans->lang('At given time');
                $detail['start'] = $event["Execute at"];
            // $detail['end'] = '';
            } else {
                $detail['schedule'] = $this->trans->lang('Every') . " " .
                    $event["Interval value"] . " " . $event["Interval field"];
                $detail['start'] = $event["Starts"];
                // $detail['end'] = '';
            }
            $details[] = $detail;
        }

        return \compact('headers', 'details');
    }
}
