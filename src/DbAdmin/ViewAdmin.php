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
            $this->viewStatus = $this->driver->tableStatusOrName($table, true);
        }
        return $this->viewStatus;
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param string $set New item options, NULL for no new item
     *
     * @return array
     */
    protected function getViewLinks($set = null)
    {
        $links = [
            'select' => $this->trans->lang('Select data'),
        ];
        if ($this->driver->support('indexes')) {
            $links['table'] = $this->trans->lang('Show structure');
        }
        if ($this->driver->support('table')) {
            $links['table'] = $this->trans->lang('Show structure');
            $links['alter'] = $this->trans->lang('Alter view');
        }
        if ($set !== null) {
            $links['edit'] = $this->trans->lang('New item');
        }
        // $links['docs'] = \doc_link([$this->driver->jush() => $this->driver->tableHelp($name)], '?');

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
            'edit-view' => $this->trans->lang('Edit view'),
            'drop-view' => $this->trans->lang('Drop view'),
        ];

        // From table.inc.php
        $status = $this->status($table);
        $name = $this->util->tableName($status);
        $title = ($status->engine == 'materialized view' ? $this->trans->lang('Materialized view') :
            $this->trans->lang('View')) . ': ' . ($name != '' ? $name : $this->util->html($table));

        $comment = $status->comment;

        $tabs = [
            'fields' => $this->trans->lang('Columns'),
            // 'indexes' => $this->trans->lang('Indexes'),
            // 'foreign-keys' => $this->trans->lang('Foreign keys'),
            // 'triggers' => $this->trans->lang('Triggers'),
        ];
        if ($this->driver->support('view_trigger')) {
            $tabs['triggers'] = $this->trans->lang('Triggers');
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
        $fields = $this->driver->fields($table);
        if (empty($fields)) {
            throw new Exception($this->driver->error());
        }

        $mainActions = $this->getViewLinks();

        $tabs = [
            'fields' => $this->trans->lang('Columns'),
            // 'triggers' => $this->trans->lang('Triggers'),
        ];
        if ($this->driver->support('view_trigger')) {
            $tabs['triggers'] = $this->trans->lang('Triggers');
        }

        $headers = [
            $this->trans->lang('Name'),
            $this->trans->lang('Type'),
            $this->trans->lang('Collation'),
        ];
        $hasComment = $this->driver->support('comment');
        if ($hasComment) {
            $headers[] = $this->trans->lang('Comment');
        }

        $details = [];
        foreach ($fields as $field) {
            $type = $this->util->html($field->fullType);
            if ($field->null) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($field->autoIncrement) {
                $type .= ' <i>' . $this->trans->lang('Auto Increment') . '</i>';
            }
            if ($field->hasDefault) {
                $type .= /*' ' . $this->trans->lang('Default value') .*/ ' [<b>' . $this->util->html($field->default) . '</b>]';
            }
            $detail = [
                'name' => $this->util->html($field->name),
                'type' => $type,
                'collation' => $this->util->html($field->collation),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->util->html($field->comment);
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
        if (!$this->driver->support('view_trigger')) {
            return null;
        }

        $mainActions = [
            $this->trans->lang('Add trigger'),
        ];

        $headers = [
            $this->trans->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        $details = [];
        // From table.inc.php
        $triggers = $this->driver->triggers($table);
        foreach ($triggers as $name => $trigger) {
            $details[] = [
                $this->util->html($trigger->timing),
                $this->util->html($trigger->event),
                $this->util->html($name),
                $this->trans->lang('Alter'),
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
        $values = $this->driver->view($view);
        $error = $this->driver->error();
        if (($error)) {
            throw new Exception($error);
        }

        return ['view' => $values];
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
        $success = $this->admin->createView($values);
        $message = $this->trans->lang('View has been created.');
        $error = $this->driver->error();

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
        $result = $this->admin->updateView($view, $values);
        $message = $this->trans->lang("View has been $result.");
        $error = $this->driver->error();
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
        $success = $this->admin->dropView($view);
        $message = $this->trans->lang('View has been dropped.');
        $error = $this->driver->error();

        return \compact('success', 'message', 'error');
    }
}
