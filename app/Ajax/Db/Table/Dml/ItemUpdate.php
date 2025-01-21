<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dml;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 * @after showBreadcrumbs
 */
class ItemUpdate extends Component
{
    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'adminer-table-query-form';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        //
    }

    /**
     * Show the update query form
     *
     * @after showBreadcrumbs
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return void
     */
    public function showUpdate(array $rowIds)
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table, $rowIds, 'Edit item');
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->lang('Error'));
            return;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $options = pm()->form($this->queryFormId);
        $actions = [
            [$this->trans->lang('Back'), $this->rq()->backToSelect(), true],
            [$this->trans->lang('Save'), $this->rq()->execUpdate($rowIds, $options)
                ->confirm($this->lang('Save this item?'))],
        ];
        $this->cl(PageActions::class)->refresh($actions);

        $content = $this->ui->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->cl(Content::class)->showHtml($content);
    }

    /**
     * Get back to the select query from which the update or delete was called
     *
     * @databag('name' => 'dbadmin.select')
     *
     * @return void
     */
    public function backToSelect()
    {
        // $select = $this->cl(Select::class);
        // $select->show(false);
        // $select->execSelect();
    }

    /**
     * Execute the update query
     *
     * @databag('name' => 'dbadmin.select')
     * @after('call' => 'debugQueries')
     *
     * @param array  $rowIds        The row selector
     * @param array  $options       The query options
     *
     * @return void
     */
    public function execUpdate(array $rowIds, array $options)
    {
        $options['where'] = $rowIds['where'];
        $options['null'] = $rowIds['null'];

        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->updateItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->lang('Error'));
            return;
        }
        $this->response->dialog->success($results['message'], $this->lang('Success'));
        $this->backToSelect();
    }
}
