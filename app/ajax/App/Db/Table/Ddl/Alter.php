<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl;

use Lagdo\DbAdmin\Ajax\App\Db\Table\ContentComponent;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function array_values;
use function Jaxon\pm;

/**
 * Alter a table
 *
 * @databag dbadmin.table
 * @after showBreadcrumbs
 */
class Alter extends ContentComponent
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
        // Make data available to views
        $this->view()->shareValues($this->tableData);

        // Save the fields in the databag
        $fields = array_values($this->tableData['fields']);
        $editPosition = 0;
        foreach($fields as $field)
        {
            $field->editPosition = $editPosition++;
        }
        $this->bag('dbadmin.table')->set('fields', $fields);
        $this->stash()->set('table.fields', $fields);

        // Set main menu buttons
        $values = pm()->form($this->formId);
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
        return $this->ui()
            ->table($editedTable)
            ->support($this->tableData['support'])
            ->engines($this->tableData['engines'])
            ->collations($this->tableData['collations'])
            ->unsigned($this->tableData['unsigned'] ?? [])
            ->foreignKeys($this->tableData['foreignKeys'])
            ->options($this->tableData['options'])
            // ->fields($this->tableData['fields'])
            ->formId($this->formId)
            ->tableWrapper($this->rq(Columns::class));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Columns::class)->render();
    }

    /**
     * @param array  $values      The table values
     *
     * @return void
     */
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
}
