<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TriggerDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DatabaseHeader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DatabaseContent;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Exception;

use function array_filter;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_values;
use function is_array;

/**
 * Proxy to database functions
 */
class DatabaseProxy extends AbstractDriverProxy
{
    /**
     * @var QueryProcessor
     */
    private QueryProcessor $processor;

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
     * The current table status
     *
     * @var mixed
     */
    protected $viewStatus = null;

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
        return $this->databaseHeader ??= new DatabaseHeader($this);
    }

    /**
     * @return DatabaseContent
     */
    private function content(): DatabaseContent
    {
        return $this->databaseContent ??= new DatabaseContent($this);
    }

    /**
     * @param QueryProcessor $processor
     *
     * @return static
     */
    public function setProcessor(QueryProcessor $processor): static
    {
        $this->processor = $processor;
        return $this;
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
        $callback = fn(ColumnDto $column) => ['name' => $column->name];
        $tables = array_map(fn(string $table) => [
            'name' => $table,
            'columns' => array_values(array_map($callback, $this->engine()->columns($table))),
        ], $this->engine()->tableNames());

        return ['tables' => $tables];
    }

    /**
     * Get the current table status
     *
     * @param string $table
     *
     * @return TableDto
     */
    protected function status(string $table): TableDto
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
            $this->utils()->lang('View')) . ': ' . ($name !== '' ? $name : $this->utils()->html($view));

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
     * Get SQL command to drop a view
     *
     * @param string $view
     *
     * @return array
     */
    public function getDropViewQueries(string $view): array
    {
        return $this->engine()->tableStatus($view) === null ? [
            'error' => $this->utils()->lang('Invalid view %s.', $view),
        ] : [
            'queries' => $this->statement()->getDropViewsQueries([$view]),
        ];
    }

    /**
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return ExecResultDto
     */
    public function dropView(string $view): ExecResultDto
    {
        $queries = $this->getDropViewQueries($view);

        return $this->processor->executeLibraryQueries($queries);
    }
}
