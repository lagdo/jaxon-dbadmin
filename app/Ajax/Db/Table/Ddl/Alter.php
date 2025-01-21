<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_values;

/**
 * Alter a table
 *
 * @databag dbadmin.table
 * @after showBreadcrumbs
 */
class Alter extends Component
{
    /**
     * @var string
     */
    protected $formId = 'adminer-table-form';

    /**
     * Default values for tables
     * @var string[]
     */
    protected $defaults = ['autoIncrementCol' => '', 'engine' => '', 'collation' => ''];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $table = $this->bag('dbadmin')->get('db.table.name');

        $tableData = $this->db->getTableData($table);
        // Make data available to views
        $this->view()->shareValues($tableData);
        // Save the fields in the databag
        $fields = array_values($tableData['fields']);
        $this->stash()->set('table.fields', $fields);
        $this->bag('dbadmin.table')->set('fields', $fields);

        // Set main menu buttons
        $this->cl(PageActions::class)->editTable($table, $this->formId);

        $editedTable = [
            'name' => $tableData['table']->name,
            'engine' => $tableData['table']->engine,
            'collation' => $tableData['table']->collation,
            'comment' => $tableData['table']->comment,
        ];
        return $this->ui
            ->table($editedTable)
            ->support($tableData['support'])
            ->engines($tableData['engines'])
            ->collations($tableData['collations'])
            ->unsigned($tableData['unsigned'] ?? [])
            ->foreignKeys($tableData['foreignKeys'])
            ->options($tableData['options'])
            // ->fields($tableData['fields'])
            ->tableWrapper($this->formId, $this->rq(Columns::class));
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        $this->cl(Columns::class)->render();
    }

    /**
     * @param array  $values      The table values
     *
     * @return void
     */
    public function save(string $table, array $values)
    {
        // $table = $this->bag('dbadmin')->get('db.table.name');

        // $values = array_merge($this->defaults, $values);

        // $result = $this->db->alterTable($table, $values);
        // if(!$result['success'])
        // {
        //     $this->response->dialog->error($result['error']);
        //     return;
        // }

        // $this->cl(Table::class)->render();
        // $this->response->dialog->success($result['message']);
    }
}
