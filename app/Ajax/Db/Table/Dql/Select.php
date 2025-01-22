<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Query as Command;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;
use Lagdo\DbAdmin\App\Component;

use Exception;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Select extends Component
{
    /**
     * @var array
     */
    private $selectData;

    /**
     * The select form div id
     *
     * @var string
     */
    private $formOptionsId = 'adminer-table-select-options-form';

    /**
     * The select query div id
     *
     * @var string
     */
    private $txtQueryId = 'adminer-table-select-query';

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $this->selectData = $this->db->getSelectData($table);

        // Make data available to views
        $this->view()->shareValues($this->selectData);
        $this->stash()->set('select.options', $this->selectData['options']);

        // Set main menu buttons
        $this->cl(PageActions::class)->showSelect($table);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $ids = [
            'formId' => $this->formOptionsId,
            'txtQueryId' => $this->txtQueryId,
        ];

        // Click handlers on buttons
        $query = pm()->js('jaxon.dbadmin.editor.query');
        $handlers = [
            'btnExec' => $this->rq()->exec(),
            'btnEdit' => $this->rq(Command::class)->database($query),
        ];

        return $this->ui->tableSelect($ids, $handlers);
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        // Show the select options
        $this->cl(Options::class)->render();

        // Show the query
        if ($this->stash()->get('select.init', true)) {
            $this->cl(Query::class)->show($this->selectData['query']);
        }
    }

    /**
     * Show the select query form
     *
     * @after showBreadcrumbs
     *
     * @param bool   $init
     *
     * @return void
     * @throws Exception
     */
    public function table(bool $init = true)
    {
        $this->stash()->set('select.init', $init);
        $this->render();
    }

    /**
     * Execute the query
     *
     * @after('call' => 'debugQueries')
     *
     * @param integer $page The page number
     *
     * @return void
     * @throws Exception
     */
    public function exec(int $page = 0)
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options');
        if($page < 1)
        {
            $page = $this->bag('dbadmin')->get('exec.page', 1);
        }
        $this->bag('dbadmin')->set('exec.page', $page);

        $options['page'] = $page;
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->execSelect($table, $options);
        // Show the message
        $resultsId = 'adminer-table-select-results';
        if(($results['message']))
        {
            $this->response->html($resultsId, $results['message']);
            return;
        }
        // Make data available to views
        $this->view()->shareValues($results);

        // Set ids for row update/delete
        $rowIds = [];
        foreach($results['rows'] as $row)
        {
            $rowIds[] = $row["ids"];
        }
        // Note: don't use the var keyword when setting a variable,
        // because it will not make the variable globally accessible.
        // $this->response->script("jaxon.dbadmin.rowIds = JSON.parse('" . json_encode($rowIds) . "')");
        $this->response->addCommand('dbadmin.row.ids.set', ['ids' => $rowIds]);

        $btnEditRowClass = 'adminer-table-select-row-edit';
        $btnDeleteRowClass = 'adminer-table-select-row-delete';
        $content = $this->ui->selectResults($results['headers'], $results['rows'],
            $btnEditRowClass, $btnDeleteRowClass);
        $this->response->html($resultsId, $content);

        // The Jaxon ajax calls
        // $updateCall = $this->rq(Query::class)->showUpdate(pm()->js("jaxon.dbadmin.rowIds[rowId]"));
        // $deleteCall = $this->rq(Query::class)->execDelete(pm()->js("jaxon.dbadmin.rowIds[rowId]"))
        //     ->confirm($this->lang('Delete this item?'));

        // Wrap the ajax calls into functions
        // $this->response->setFunction('updateRowItem', 'rowId', $updateCall);
        // $this->response->setFunction('deleteRowItem', 'rowId', $deleteCall);

        // Set the functions as button event handlers
        // $this->response->jq(".$btnEditRowClass", "#$resultsId")->click(rq('.')->updateRowItem(jq()->attr('data-row-id')));
        // $this->response->jq(".$btnDeleteRowClass", "#$resultsId")->click(rq('.')->deleteRowItem(jq()->attr('data-row-id')));

        // Show the query
        $this->cl(Query::class)->show($results['query']);

        // Pagination
        $pages = $this->rq()->execSelect(pm()->page())->pages($page, $results['limit'], $results['total']);
        $pagination = $this->ui->pagination($pages);
        $this->response->html("adminer-table-select-pagination", $pagination);
    }
}
