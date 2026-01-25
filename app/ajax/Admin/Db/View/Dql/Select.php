<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\View\Dql;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\MainComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options\Fields;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options\Values;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryTrait;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl\View;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use QueryTrait;

    /**
     * The select form div id
     *
     * @var string
     */
    private $formOptionsId = 'dbadmin-table-select-options-form';

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // The columns, filters and sorting values are reset.
        $this->bag('dbadmin.select')->set($this->tabKey('columns'), []);
        $this->bag('dbadmin.select')->set($this->tabKey('filters'), []);
        $this->bag('dbadmin.select')->set($this->tabKey('sorting'), []);
        // While the options values are kept.
        $options = $this->bag('dbadmin.select')->get($this->tabKey('options'), []);

        $table = $this->getTableName();
        // Set the breadcrumbs
        $this->db->breadcrumbs(true)
            ->item($this->trans()->lang('Views'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans()->lang('Select'));

        // Save select queries options
        $selectData = $this->db()->getSelectData($table, $options);
        $this->bag('dbadmin.select')->set($this->tabKey('options'), [
            'limit' => (int)($selectData->options['limit']['value'] ?? 0),
            'length' => (int)($selectData->options['length']['value'] ?? 0),
        ]);
        $this->stash()->set('select.query', $selectData->query);

        // Set main menu buttons
        $actions = [
            'show-table' => [
                'title' => $this->trans()->lang('Show view'),
                'handler' => $this->rq(View::class)->show($table),
            ],
            'edit-view' => [
                'title' => $this->trans()->lang('Edit view'),
                'handler' => $this->rq(Form::class)->edit($table),
            ],
            'back-tables' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->selectUi->home($this->formOptionsId);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        // Show the select options
        $this->cl(Fields::class)->render();
        $this->cl(Values::class)->render();
        // Show the query
        $this->cl(QueryText::class)->render();
    }

    /**
     * Show the select query form
     *
     * @param string $table       The table name
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(string $table): void
    {
        // Save the table name in the databag.
        $this->bag('dbadmin.table')->set($this->tabKey('name'), $table);
        // Save the current page in the databag
        $this->savePageNumber(1);

        $this->render();
    }

    /**
     * Edit the current select query
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function edit(): void
    {
        $this->cl(QueryEdit::class)->database($this->getSelectQuery());
    }
}
