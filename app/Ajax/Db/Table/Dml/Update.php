<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dml;

use Lagdo\DbAdmin\App\Ajax\Db\Table\ContentComponent;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 * @after showBreadcrumbs
 */
class Update extends ContentComponent
{
    /**
     * @var array
     */
    private $rowIds;

    /**
     * @var array
     */
    private $queryData;

    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'adminer-table-query-form';

    /**
     * @inheritDoc
     */
    protected function before()
    {
        // Make data available to views
        $this->view()->shareValues($this->queryData);

        // Set main menu buttons
        $options = pm()->form($this->queryFormId);
        $actions = [
            [$this->trans()->lang('Back'), $this->rq()->back(), true],
            [$this->trans()->lang('Save'), $this->rq()->exec($this->rowIds, $options)
                ->confirm($this->trans()->lang('Save this item?'))],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->tableQueryForm($this->queryFormId, $this->queryData['fields']);
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
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
    public function show(array $rowIds)
    {
        $this->rowIds = $rowIds;
        $this->queryData = $this->db()
            ->getQueryData($this->getTableName(), $rowIds, 'Edit item');
        // Show the error
        if(($this->queryData['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($this->queryData['error']);
            return;
        }
        $this->render();
    }

    /**
     * Get back to the select query from which the update or delete was called
     *
     * @databag('name' => 'dbadmin.select')
     *
     * @return void
     */
    public function back()
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
    public function exec(array $rowIds, array $options)
    {
        $options['where'] = $rowIds['where'];
        $options['null'] = $rowIds['null'];

        $results = $this->db()->updateItem($this->getTableName(), $options);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->trans()->lang('Success'))->success($results['message']);
        $this->back();
    }
}
