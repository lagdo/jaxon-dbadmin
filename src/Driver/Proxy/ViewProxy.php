<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Exception;

use function compact;

/**
 * Proxy to view functions
 */
class ViewProxy extends AbstractDriverProxy
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
            $this->viewStatus = $this->engine()->tableStatusOrName($table, true);
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
        $name = $this->pageUi()->tableName($status);
        $title = ($status->engine == 'materialized view' ? $this->utils()->lang('Materialized view') :
            $this->utils()->lang('View')) . ': ' . ($name != '' ? $name : $this->utils()->html($view));

        $comment = $status->comment;

        $tabs = [
            'fields' => $this->utils()->lang('Columns'),
            // 'indexes' => $this->utils()->lang('Indexes'),
            // 'foreign-keys' => $this->utils()->lang('Foreign keys'),
            // 'triggers' => $this->utils()->lang('Triggers'),
        ];
        if ($this->engine()->support('view_trigger')) {
            $tabs['triggers'] = $this->utils()->lang('Triggers');
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
        $fields = $this->engine()->fields($view);
        if (empty($fields)) {
            throw new Exception($this->engine()->error());
        }

        $tabs = [
            'fields' => $this->utils()->lang('Columns'),
            // 'triggers' => $this->utils()->lang('Triggers'),
        ];
        if ($this->engine()->support('view_trigger')) {
            $tabs['triggers'] = $this->utils()->lang('Triggers');
        }

        $headers = [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Collation'),
        ];
        $hasComment = $this->engine()->support('comment');
        if ($hasComment) {
            $headers[] = $this->utils()->lang('Comment');
        }

        $details = [];
        foreach ($fields as $field) {
            $type = $this->utils()->html($field->fullType);
            if ($field->nullable) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($field->autoIncrement) {
                $type .= ' <i>' . $this->utils()->lang('Auto Increment') . '</i>';
            }
            if ($field->hasDefault()) {
                $type .= /*' ' . $this->utils()->lang('Default value') .*/ ' [<b>' .
                    $this->utils()->html($field->default) . '</b>]';
            }
            $detail = [
                'name' => $this->utils()->html($field->name),
                'type' => $type,
                'collation' => $this->utils()->html($field->collation),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->utils()->html($field->comment);
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
        if (!$this->engine()->support('view_trigger')) {
            return null;
        }

        $headers = [
            $this->utils()->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        $details = [];
        // From table.inc.php
        $triggers = $this->engine()->triggers($view);
        foreach ($triggers as $name => $trigger) {
            $details[] = [
                $this->utils()->html($trigger->timing),
                $this->utils()->html($trigger->event),
                $this->utils()->html($name),
                $this->utils()->lang('Alter'),
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
        $values = $this->engine()->view($view);
        $error = $this->engine()->error();
        if (($error)) {
            throw new Exception($error);
        }

        return [
            'view' => $values,
            'materialized' => $this->engine()->support('materializedview'),
        ];
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
        $success = $this->engine()->createView($values);
        $message = $this->utils()->lang('View has been created.');
        $error = $this->engine()->error();

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
        $result = $this->engine()->updateView($view, $values);
        $message = $this->utils()->lang("View has been $result.");
        $error = $this->engine()->error();
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
        if ($this->engine()->tableStatus($view) === null) {
            return [
                'error' => $this->utils()->lang('Invalid view %s.', $view),
            ];
        }

        if (!$this->engine()->dropView($view)) {
            return [
                'error' => $this->engine()->error(),
            ];
        }

        return [
            'message' => $this->utils()->lang('View has been dropped.'),
        ];
    }
}
