<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dml;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 * @after showBreadcrumbs
 */
class ItemInsert extends Component
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
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table);
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->lang('Error'));
            return $this->response;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $this->cl(PageActions::class)->showQuery($table, $this->queryFormId, true);

        $content = $this->ui->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->cl(Content::class)->showHtml($content);

        return $this->response;
    }

    /**
     * Execute the insert query
     *
     * @after('call' => 'debugQueries')
     *
     * @param array  $options     The query options
     * @param bool $addNew        Add a new entry after saving the current one.
     *
     * @return Response
     */
    public function execInsert(array $options, bool $addNew): Response
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->insertItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->lang('Success'));

        // $addNew ? $this->render() : $this->cl(Select::class)->show();

        return $this->response;
    }
}
