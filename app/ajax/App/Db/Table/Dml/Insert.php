<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\App\Db\Table\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function Jaxon\je;

/**
 * This class provides insert and update query features on tables.
 */
class Insert extends FuncComponent
{
    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'dbadmin-table-query-form';

    /**
     * @return void
     */
    public function show(): void
    {
        $queryData = $this->db()->getInsertData($this->getTableName());
        // Show the error
        if(isset($queryData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($queryData['error']);
            return;
        }

        $title = 'New item';
        $content = $this->tableUi->formId($this->formId)
            ->queryForm($queryData['fields'], '400px');
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Save'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(je($this->queryFormId)->rd()->form())
                ->confirm($this->trans()->lang('Save this item?')),
        ]];
        $this->modal()->show($title, $content, $buttons, $options);
    }


    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // Set main menu buttons
        $table = $this->getTableName();
        $options = je($this->queryFormId)->rd()->form();
        $actions = [
            'query-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq()->exec($options, true)
                    ->confirm($this->trans()->lang('Save this item?')),
            ],
            'query-save-select' => [
                'title' => $this->trans()->lang('Save and select'),
                'handler' => $this->rq()->exec($options, false)
                    ->confirm($this->trans()->lang('Save this item?')),
            ],
            'query-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * Show the update query form
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function showf(): void
    {
        $this->queryData = $this->db()->getInsertData($this->getTableName());
        // Show the error
        if(($this->queryData['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($this->queryData['error']);
            return;
        }
        $this->render();
    }

    /**
     * Execute the insert query
     *
     * @param array  $options     The query options
     * @param bool $addNew        Add a new entry after saving the current one.
     *
     * @return void
     */
    public function exec(array $options, bool $addNew): void
    {
        $table = $this->getTableName();
        $results = $this->db()->insertItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->trans()->lang('Success'))->success($results['message']);

        $addNew ? $this->render() : $this->cl(Select::class)->show($table);
    }
}
