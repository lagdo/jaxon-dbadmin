<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Exception;

use function compact;

/**
 * Facade to view functions
 */
class ViewFacade extends AbstractFacade
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
     * Get details about a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewInfo(string $view): array
    {
        // From table.inc.php
        $status = $this->status($view);
        $name = $this->admin->tableName($status);
        $title = ($status->engine == 'materialized view' ? $this->trans->lang('Materialized view') :
            $this->trans->lang('View')) . ': ' . ($name != '' ? $name : $this->admin->html($view));

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

        return compact('title', 'comment', 'tabs');
    }

    /**
     * Get the fields of a table or a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getViewFields(string $view): array
    {
        // From table.inc.php
        $fields = $this->driver->fields($view);
        if (empty($fields)) {
            throw new Exception($this->driver->error());
        }

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
            $type = $this->admin->html($field->fullType);
            if ($field->null) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($field->autoIncrement) {
                $type .= ' <i>' . $this->trans->lang('Auto Increment') . '</i>';
            }
            if ($field->hasDefault) {
                $type .= /*' ' . $this->trans->lang('Default value') .*/ ' [<b>' . $this->admin->html($field->default) . '</b>]';
            }
            $detail = [
                'name' => $this->admin->html($field->name),
                'type' => $type,
                'collation' => $this->admin->html($field->collation),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->admin->html($field->comment);
            }

            $details[] = $detail;
        }

        return compact('headers', 'details');
    }

    /**
     * Get the triggers of a table
     *
     * @param string $view     The view name
     *
     * @return array|null
     */
    public function getViewTriggers(string $view): ?array
    {
        if (!$this->driver->support('view_trigger')) {
            return null;
        }

        $headers = [
            $this->trans->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        $details = [];
        // From table.inc.php
        $triggers = $this->driver->triggers($view);
        foreach ($triggers as $name => $trigger) {
            $details[] = [
                $this->admin->html($trigger->timing),
                $this->admin->html($trigger->event),
                $this->admin->html($name),
                $this->trans->lang('Alter'),
            ];
        }

        return compact('headers', 'details');
    }

    /**
     * Get a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getView(string $view): array
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
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function createView(array $values): array
    {
        $success = $this->driver->createView($values);
        $message = $this->trans->lang('View has been created.');
        $error = $this->driver->error();

        return compact('success', 'message', 'error');
    }

    /**
     * Update a view
     *
     * @param string $view The view name
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function updateView(string $view, array $values): array
    {
        $result = $this->driver->updateView($view, $values);
        $message = $this->trans->lang("View has been $result.");
        $error = $this->driver->error();
        $success = !$error;

        return compact('success', 'message', 'error');
    }

    /**
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function dropView(string $view): array
    {
        $success = $this->driver->dropView($view);
        $message = $this->trans->lang('View has been dropped.');
        $error = $this->driver->error();

        return compact('success', 'message', 'error');
    }
}
