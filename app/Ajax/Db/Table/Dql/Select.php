<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Db\Table\ContentComponent;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use Exception;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Select extends ContentComponent
{
    use QueryTrait;

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
        $this->bag('dbadmin.select')->set('options', ['limit' => 50, 'text_length' => 100]);
        $this->bag('dbadmin.select')->set('columns', []);
        $this->bag('dbadmin.select')->set('filters', []);
        $this->bag('dbadmin.select')->set('sorting', []);

        $table = $this->bag('dbadmin')->get('db.table.name');

        // Save select queries options
        $selectData = $this->db()->getSelectData($table);
        $this->stash()->set('select.options', $selectData['options']);
        $this->stash()->set('select.query', $selectData['query']);

        // Set main menu buttons
        $backToTables = $this->stash()->get('back.tables', false);
        $actions = [
            // 'select-exec' => [
            //     'title' => $this->trans()->lang('Execute'),
            //     'handler' => $this->rq(Results::class)->page(),
            // ],
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(Insert::class)->show(),
            ],
            'select-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $backToTables ? $this->rq(Tables::class)->show() :
                    $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
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
        $handlers = [
            'btnExec' => $this->rq(Results::class)->page(),
            'btnEdit' => $this->rq()->edit(),
        ];

        return $this->ui()->tableSelect($ids, $handlers);
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        // Show the select options
        $this->cl(Options::class)->render();
        // Show the query
        $this->cl(QueryText::class)->render();
    }

    /**
     * Show the select query form
     *
     * @after showBreadcrumbs
     *
     * @param string $table       The table name
     * @param bool $backToTables
     *
     * @return void
     */
    public function show(string $table, bool $backToTables = false)
    {
        $this->stash()->set('back.tables', $backToTables);
        // Save the table name in the databag.
        $this->bag('dbadmin')->set('db.table.name', $table);

        $this->render();
    }

    /* *
     * Execute the query (No more used, to be deleted)
     *
     * @after('call' => 'debugQueries')
     *
     * @param integer $page The page number
     *
     * @return void
     * @throws Exception
     */
    // public function exec(int $page = 0)
    // {
    //     $table = $this->bag('dbadmin')->get('db.table.name');
    //     // Select options
    //     $options = $this->getOptions();
    //     $options['page'] = $page;
    //     $results = $this->db()->execSelect($table, $options);

    //     // Show the message
    //     $resultsId = 'adminer-table-select-results';
    //     if(($results['message']))
    //     {
    //         $this->response->html($resultsId, $results['message']);
    //         return;
    //     }
    //     // Make data available to views
    //     $this->view()->shareValues($results);

    //     // Set ids for row update/delete
    //     $rowIds = [];
    //     foreach($results['rows'] as $row)
    //     {
    //         $rowIds[] = $row["ids"];
    //     }
    //     // Note: don't use the var keyword when setting a variable,
    //     // because it will not make the variable globally accessible.
    //     $this->response->script("jaxon.dbadmin.rowIds = JSON.parse('" . json_encode($rowIds) . "')");
    //     $this->response->addCommand('dbadmin.row.ids.set', ['ids' => $rowIds]);

    //     $content = $this->ui()->selectResults($results['headers'], $results['rows']);
    //     $this->response->html($resultsId, $content);

    //     // The Jaxon ajax calls
    //     $updateCall = $this->rq(Query::class)->showUpdate(pm()->js("jaxon.dbadmin.rowIds[rowId]"));
    //     $deleteCall = $this->rq(Query::class)->execDelete(pm()->js("jaxon.dbadmin.rowIds[rowId]"))
    //         ->confirm($this->trans()->lang('Delete this item?'));

    //     // Wrap the ajax calls into functions
    //     $this->response->setFunction('updateRowItem', 'rowId', $updateCall);
    //     $this->response->setFunction('deleteRowItem', 'rowId', $deleteCall);

    //     // Set the functions as button event handlers
    //     $this->response->jq(".$btnEditRowClass", "#$resultsId")->click(rq('.')->updateRowItem(jq()->attr('data-row-id')));
    //     $this->response->jq(".$btnDeleteRowClass", "#$resultsId")->click(rq('.')->deleteRowItem(jq()->attr('data-row-id')));

    //     // Pagination
    //     $pages = $this->rq()->execSelect(pm()->page())->pages($page, $results['limit'], $results['total']);
    //     $pagination = $this->ui()->pagination($pages);
    //     $this->response->html("adminer-table-select-pagination", $pagination);
    // }

    /**
     * Edit the current select query
     *
     * @return void
     */
    public function edit()
    {
        $this->cl(QueryEdit::class)->database($this->getSelectQuery());
    }
}
