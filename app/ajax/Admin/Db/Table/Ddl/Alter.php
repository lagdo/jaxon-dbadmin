<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_values;
use function Jaxon\je;

/**
 * Alter or drop a table
 */
#[Databag('dbadmin.table')]
#[After('showBreadcrumbs')]
#[Export(['render'])]
class Alter extends MainComponent
{
    /**
     * @var array
     */
    private $tableData;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * Default values for tables
     *
     * @var string[]
     */
    protected $defaults = ['autoIncrementCol' => '', 'engine' => '', 'collation' => ''];

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getTableName();
        $this->tableData = $this->db()->getTableData($table);

        $fields = array_values($this->tableData['fields']);
        $editPosition = 0;
        foreach($fields as $field)
        {
            $field->editPosition = $editPosition++;
        }

        // Save the fields in the databag
        $callback = fn(TableFieldEntity $field) => $field->toArray();
        $this->bag('dbadmin.table')->set('fields', array_map($callback, $fields));
        $this->stash()->set('table.fields', $fields);

        // Set main menu buttons
        $values = je($this->formId)->rd()->form();
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq()->save($table, $values)
                    ->confirm("Save changes on table $table?"),
            ],
            'table-cancel' => [
                'title' => $this->trans()->lang('Cancel'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $editedTable = [
            'name' => $this->tableData['table']->name,
            'engine' => $this->tableData['table']->engine,
            'collation' => $this->tableData['table']->collation,
            'comment' => $this->tableData['table']->comment,
        ];
        return $this->tableUi
            ->table($editedTable)
            ->support($this->tableData['support'])
            ->engines($this->tableData['engines'])
            ->collations($this->tableData['collations'])
            ->unsigned($this->tableData['unsigned'] ?? [])
            ->foreignKeys($this->tableData['foreignKeys'])
            ->options($this->tableData['options'])
            // ->fields($this->tableData['fields'])
            ->formId($this->formId)
            ->wrapper($this->rq(Columns::class));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Columns::class)->render();
    }

    /**
     * @param string $table      The table name
     * @param array  $values      The table values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function save(string $table, array $values): void
    {
        // $table = $this->getTableName();

        // $values = array_merge($this->defaults, $values);

        // $result = $this->db()->alterTable($table, $values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->cl(Table::class)->render();
        // $this->alert()->success($result['message']);
    }

    /**
     * @param string $table      The table name
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function drop(string $table): void
    {
    }
}
