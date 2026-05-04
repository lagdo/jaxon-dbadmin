<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TriggerDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;
use Exception;

use function array_keys;
use function array_map;

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
        return $this->viewStatus ??= $this->engine()->tableStatusOrName($table, true);
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

        $tabs = [
            'columns' => $this->utils()->lang('Columns'),
        ];
        if ($this->engine()->support('view_trigger')) {
            $tabs['triggers'] = $this->utils()->lang('Triggers');
        }

        return [
            'title' => $title,
            'comment' => $status->comment,
            'tabs' => $tabs,
        ];
    }

    /**
     * Get the columns of a table or a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getViewColumns(string $view): array
    {
        // From table.inc.php
        $columns = $this->engine()->columns($view);
        if (empty($columns)) {
            throw new Exception($this->engine()->error());
        }

        $headers = [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Collation'),
        ];
        $commentSupported = $this->engine()->support('comment');
        if ($commentSupported) {
            $headers[] = $this->utils()->lang('Comment');
        }

        $details = array_map(function(ColumnDto $column) use($commentSupported) {
            $type = $this->utils()->html($column->fullType);
            if ($column->nullable) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($column->autoIncrement) {
                $type .= ' <i>' . $this->utils()->lang('Auto Increment') . '</i>';
            }
            if ($column->hasDefault()) {
                $type .= /*' ' . $this->utils()->lang('Default value') .*/ ' [<b>' .
                    $this->utils()->html($column->default) . '</b>]';
            }
            $detail = [
                'name' => $this->utils()->html($column->name),
                'type' => $type,
                'collation' => $this->utils()->html($column->collation),
            ];
            if ($commentSupported) {
                $detail['comment'] = $column->comment === null ? null :
                    $this->utils()->html($column->comment);
            }

            return new DetailDto($detail);
        }, $columns);

        return [
            'headers' => $headers,
            'details' => $details,
        ];
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

        // From table.inc.php
        $triggers = $this->engine()->triggers($view);
        $details = array_map(fn(TriggerDto $trigger, string $name) => new DetailDto([
            $this->utils()->html($trigger->timing),
            $this->utils()->html($trigger->event),
            $this->utils()->html($name),
            $this->utils()->lang('Alter'),
        ]), $triggers, array_keys($triggers));

        return [
            'headers' => [
                $this->utils()->lang('Name'),
                '&nbsp;',
                '&nbsp;',
                '&nbsp;',
            ],
            'details' => $details,
        ];
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
        return [
            'success' => $this->engine()->createView($values),
            'message' => $this->utils()->lang('View has been created.'),
            'error' => $this->engine()->error(),
        ];
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
        $error = $this->engine()->error();

        return [
            'success' => !$error,
            'message' => $this->utils()->lang("View has been $result."),
            'error' => $error,
        ];
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
        return match(true) {
            !$this->engine()->tableStatus($view) => [
                'error' => $this->utils()->lang('Invalid view %s.', $view),
            ],
            !$this->engine()->dropView($view) => [
                'error' => $this->engine()->error(),
            ],
            default => [
                'message' => $this->utils()->lang('View has been dropped.'),
            ],
        };
    }
}
