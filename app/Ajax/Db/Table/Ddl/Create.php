<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

/**
 * Create a new table
 *
 * @databag dbadmin.table
 * @after showBreadcrumbs
 */
class Create extends Component
{
    /**
     * @var string
     */
    protected $formId = 'adminer-table-form';

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->bag('dbadmin')->set('db.table.name', '');
        $this->bag('dbadmin.table')->set('fields', []);
        $this->cache()->set('table.fields', []);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $tableData = $this->db->getTableData();
        // Make data available to views
        $this->view()->shareValues($tableData);

        // Set main menu buttons
        $this->cl(PageActions::class)->addTable($this->formId);

        return $this->ui
            ->support($tableData['support'])
            ->engines($tableData['engines'])
            ->collations($tableData['collations'])
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
     * Create a new table
     *
     * @param array  $values      The table values
     *
     * @return Response
     */
    public function save(array $values)
    {
        $fields = $this->bag('dbadmin.table')->get('fields');
        // $values = array_merge($this->defaults, $values);

        // $result = $this->db->createTable($values);
        // if(!$result['success'])
        // {
        //     $this->response->dialog->error($result['error']);
        //     return $this->response;
        // }

        // $this->show($values['name']);
        // $this->response->dialog->success($result['message']);
        return $this->response;
    }
}
