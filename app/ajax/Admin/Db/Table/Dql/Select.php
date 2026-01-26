<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use QueryTrait;

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // The columns, filters and sorting values are reset.
        $this->setSelectBag('columns', []);
        $this->setSelectBag('filters', []);
        $this->setSelectBag('sorting', []);
        // While the options values are kept.
        $options = $this->getSelectBag('options', []);

        $table = $this->getCurrentTable();
        // Set the breadcrumbs
        $this->db->breadcrumbs(true)
            ->item($this->trans->lang('Tables'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans->lang('Select'));

        // Save select queries options
        $selectData = $this->db()->getSelectData($table, $options);
        $this->setSelectBag('options', [
            'limit' => (int)($selectData->options['limit']['value'] ?? 0),
            'total' => (bool)($options['total'] ?? true), // Keep the same value.
            'length' => (int)($selectData->options['length']['value'] ?? 100),
        ]);
        $this->stash()->set('select.query', $selectData->query);

        // Set main menu buttons
        $actions = [
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(Insert::class)->show(true),
            ],
            'show-table' => [
                'title' => $this->trans()->lang('Show table'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
            'back-tables' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Tables::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->selectUi->home();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        // Show the select options
        $this->cl(Options\Fields::class)->render();
        $this->cl(Options\Values::class)->render();
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
        $this->setCurrentTable($table);
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
