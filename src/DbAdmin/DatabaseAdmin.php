<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin database functions
 */
class DatabaseAdmin extends AbstractAdmin
{
    /**
     * The final schema list
     *
     * @var array
     */
    protected $finalSchemas = null;

    /**
     * The schemas the user has access to
     *
     * @var array
     */
    protected $userSchemas = null;

    /**
     * The constructor
     *
     * @param array $options    The server config options
     */
    public function __construct(array $options)
    {
        // Set the user schemas, if defined.
        if (\array_key_exists('access', $options) &&
            \is_array($options['access']) &&
            \array_key_exists('schemas', $options['access']) &&
            \is_array($options['access']['schemas'])) {
            $this->userSchemas = $options['access']['schemas'];
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
            if (\is_array($this->userSchemas)) {
                // Only keep schemas that appear in the config.
                $this->finalSchemas = \array_intersect($this->finalSchemas, $this->userSchemas);
                $this->finalSchemas = \array_values($this->finalSchemas);
            }
        }
        return $this->finalSchemas;
    }

    /**
     * Connect to a database server
     *
     * @return void
     */
    public function getDatabaseInfo()
    {
        $sqlActions = [
            'database-command' => $this->trans->lang('SQL command'),
            'database-import' => $this->trans->lang('Import'),
            'database-export' => $this->trans->lang('Export'),
        ];

        $menuActions = [
            'table' => $this->trans->lang('Tables'),
            // 'view' => $this->trans->lang('Views'),
            // 'routine' => $this->trans->lang('Routines'),
            // 'sequence' => $this->trans->lang('Sequences'),
            // 'type' => $this->trans->lang('User types'),
            // 'event' => $this->trans->lang('Events'),
        ];
        if ($this->driver->support('view')) {
            $menuActions['view'] = $this->trans->lang('Views');
        }
        // Todo: Implement features and enable menu items.
        // if ($this->driver->support('routine')) {
        //     $menuActions['routine'] = $this->trans->lang('Routines');
        // }
        // if ($this->driver->support('sequence')) {
        //     $menuActions['sequence'] = $this->trans->lang('Sequences');
        // }
        // if ($this->driver->support('type')) {
        //     $menuActions['type'] = $this->trans->lang('User types');
        // }
        // if ($this->driver->support('event')) {
        //     $menuActions['event'] = $this->trans->lang('Events');
        // }

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

        return \compact('sqlActions', 'menuActions', 'schemas'/*, 'tables'*/);
    }

    /**
     * Get the tables from a database server
     *
     * @return void
     */
    public function getTables()
    {
        $mainActions = [
            'add-table' => $this->trans->lang('Create table'),
        ];

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
        // $tableStatus = $this->driver->tableStatus('', true); // Tables details
        $tableStatus = $this->driver->tableStatus(); // Tables details

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
        return \compact('mainActions', 'headers', 'details', 'select');
    }

    /**
     * Get the views from a database server
     * Almost the same as getTables()
     *
     * @return void
     */
    public function getViews()
    {
        $mainActions = [
            'add-view' => $this->trans->lang('Create view'),
        ];

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
        // $tableStatus = $this->driver->tableStatus('', true); // Tables details
        $tableStatus = $this->driver->tableStatus(); // Tables details

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

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return void
     */
    public function getRoutines()
    {
        $mainActions = [
            'procedure' => $this->trans->lang('Create procedure'),
            'function' => $this->trans->lang('Create function'),
        ];

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

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return void
     */
    public function getSequences()
    {
        $mainActions = [
            'sequence' => $this->trans->lang('Create sequence'),
        ];

        $headers = [
            $this->trans->lang('Name'),
        ];

        $sequences = [];
        if ($this->driver->support("sequence")) {
            // From db.inc.php
            $sequences = $this->driver->values("SELECT sequence_name FROM information_schema.sequences ".
                "WHERE sequence_schema = selectedSchema() ORDER BY sequence_name");
        }
        $details = [];
        foreach ($sequences as $sequence) {
            $details[] = [
                'name' => $this->util->html($sequence),
            ];
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getUserTypes()
    {
        $mainActions = [
            'type' => $this->trans->lang('Create type'),
        ];

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

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getEvents()
    {
        $mainActions = [
            'event' => $this->trans->lang('Create event'),
        ];

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

        return \compact('mainActions', 'headers', 'details');
    }
}
