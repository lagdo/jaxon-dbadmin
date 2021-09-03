<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin view functions
 */
class ViewAdmin extends AbstractAdmin
{
    /**
     * The current table status
     *
     * @var mixed
     */
    protected $viewStatus = null;

    /**
     * Get the current table status
     *
     * @param string $table
     *
     * @return mixed
     */
    protected function status(string $table)
    {
        if (!$this->viewStatus) {
            $this->viewStatus = $this->db->tableStatusOrName($table, true);
        }
        return $this->viewStatus;
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param string new item options, NULL for no new item
     *
     * @return array
     */
    protected function getViewLinks($set = null)
    {
        $links = [
            "select" => $this->util->lang('Select data'),
        ];
        if ($this->db->support("indexes")) {
            $links["table"] = $this->util->lang('Show structure');
        }
        if ($this->db->support("table")) {
            $links["table"] = $this->util->lang('Show structure');
            $links["alter"] = $this->util->lang('Alter view');
        }
        if ($set !== null) {
            $links["edit"] = $this->util->lang('New item');
        }
        // $links['docs'] = \doc_link([$this->db->jush() => $this->db->tableHelp($name)], "?");

        return $links;
    }

    /**
     * Get details about a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewInfo(string $table)
    {
        $mainActions = [
            'edit-view' => $this->util->lang('Edit view'),
            'drop-view' => $this->util->lang('Drop view'),
        ];

        // From table.inc.php
        $status = $this->status($table);
        $name = $this->util->tableName($status);
        $title = ($status['Engine'] == 'materialized view' ?
            $this->util->lang('Materialized view') : $this->util->lang('View')) .
            ": " . ($name != "" ? $name : $this->util->html($table));

        $comment = $status["Comment"] ?? '';

        $tabs = [
            'fields' => $this->util->lang('Columns'),
            // 'indexes' => $this->util->lang('Indexes'),
            // 'foreign-keys' => $this->util->lang('Foreign keys'),
            // 'triggers' => $this->util->lang('Triggers'),
        ];
        if ($this->db->support("view_trigger")) {
            $tabs['triggers'] = $this->util->lang('Triggers');
        }

        return \compact('mainActions', 'title', 'comment', 'tabs');
    }

    /**
     * Get the fields of a table or a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewFields(string $table)
    {
        // From table.inc.php
        $fields = $this->db->fields($table);
        if (!$fields) {
            throw new Exception($this->util->error());
        }

        $mainActions = $this->getViewLinks();

        $tabs = [
            'fields' => $this->util->lang('Columns'),
            // 'triggers' => $this->util->lang('Triggers'),
        ];
        if ($this->db->support("view_trigger")) {
            $tabs['triggers'] = $this->util->lang('Triggers');
        }

        $headers = [
            $this->util->lang('Name'),
            $this->util->lang('Type'),
            $this->util->lang('Collation'),
        ];
        $hasComment = $this->db->support('comment');
        if ($hasComment) {
            $headers[] = $this->util->lang('Comment');
        }

        $details = [];
        foreach ($fields as $field) {
            $type = $this->util->html($field["full_type"]);
            if ($field["null"]) {
                $type .= " <i>nullable</i>"; // " <i>NULL</i>";
            }
            if ($field["auto_increment"]) {
                $type .= " <i>" . $this->util->lang('Auto Increment') . "</i>";
            }
            if (\array_key_exists("default", $field)) {
                $type .= /*' ' . $this->util->lang('Default value') .*/ ' [<b>' . $this->util->html($field["default"]) . '</b>]';
            }
            $detail = [
                'name' => $this->util->html($field["field"] ?? ''),
                'type' => $type,
                'collation' => $this->util->html($field["collation"] ?? ''),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->util->html($field["comment"] ?? '');
            }

            $details[] = $detail;
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the triggers of a table
     *
     * @param string $view      The view name
     *
     * @return array|null
     */
    public function getViewTriggers(string $table)
    {
        $status = $this->status($table);
        if (!$this->db->support("view_trigger")) {
            return null;
        }

        // From table.inc.php
        $triggers = $this->db->triggers($table);
        $mainActions = [
            $this->util->lang('Add trigger'),
        ];

        $headers = [
            $this->util->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        if (!$triggers) {
            $triggers = [];
        }
        $details = [];
        // From table.inc.php
        foreach ($triggers as $key => $val) {
            $details[] = [
                $this->util->html($val[0]),
                $this->util->html($val[1]),
                $this->util->html($key),
                $this->util->lang('Alter'),
            ];
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getView(string $view)
    {
        // From view.inc.php
        $orig_type = "VIEW";
        if ($this->db->jush() == "pgsql") {
            $status = $this->db->tableStatus($view);
            $orig_type = \strtoupper($status["Engine"]);
        }
        $values = $this->db->view($view);
        $values["name"] = $view;
        $values["materialized"] = ($orig_type != "VIEW");

        $error = $this->util->error();
        if (($error)) {
            throw new Exception($error);
        }

        return ['view' => $values, 'orig_type' => $orig_type];
    }

    /**
     * Create a view
     *
     * @param array  $values    The view values
     *
     * @return array
     */
    public function createView(array $values)
    {
        // From view.inc.php
        $name = \trim($values["name"]);
        $location = null; // ME . "table=" . urlencode($name);
        $message = $this->util->lang('View has been created.');
        $type = $values["materialized"] ? "MATERIALIZED VIEW" : "VIEW";

        $sql = ($this->db->jush() == "mssql" ? "ALTER" : "CREATE OR REPLACE") .
            " $type " . $this->db->table($name) . " AS\n" . $values['select'];
        $success = $this->util->queryAndRedirect($sql, $location, $message);

        $error = $this->util->error();

        return \compact('success', 'message', 'error');
    }

    /**
     * Update a view
     *
     * @param string $view      The view name
     * @param array  $values    The view values
     *
     * @return array
     */
    public function updateView(string $view, array $values)
    {
        // From view.inc.php
        $orig_type = "VIEW";
        if ($this->db->jush() == "pgsql") {
            $status = $this->db->tableStatus($view);
            $orig_type = \strtoupper($status["Engine"]);
        }

        $name = \trim($values["name"]);
        $location = null; // $_POST["drop"] ? \substr(ME, 0, -1) : ME . "table=" . \urlencode($name);
        $message = $this->util->lang('View has been altered.');
        $type = $values["materialized"] ? "MATERIALIZED VIEW" : "VIEW";
        $tempName = $name . "_adminer_" . \uniqid();

        $this->util->dropAndCreate(
            "DROP $orig_type " . $this->db->table($view),
            "CREATE $type " . $this->db->table($name) . " AS\n" . $values['select'],
            "DROP $type " . $this->db->table($name),
            "CREATE $type " . $this->db->table($tempName) . " AS\n" . $values['select'],
            "DROP $type " . $this->db->table($tempName),
            $location,
            $this->util->lang('View has been dropped.'),
            $message,
            $this->util->lang('View has been created.'),
            $view,
            $name
        );

        $error = $this->util->error();
        $success = !$error;
        return \compact('success', 'message', 'error');
    }

    /**
     * Drop a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function dropView(string $view)
    {
        // From view.inc.php
        $orig_type = "VIEW";
        if ($this->db->jush() == "pgsql") {
            $status = $this->db->tableStatus($view);
            $orig_type = \strtoupper($status["Engine"]);
        }

        $sql = "DROP $orig_type " . $this->db->table($view);
        $location = null; // $_POST["drop"] ? \substr(ME, 0, -1) : ME . "table=" . \urlencode($name);
        $message = $this->util->lang('View has been dropped.');
        $success =$this->util->drop($sql, $location, $message);

        $error = $this->util->error();

        return \compact('success', 'message', 'error');
    }
}
