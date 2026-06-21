<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\InsertFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Support\Service\Admin\QueryLogger;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use QueryTrait;

    /**
     * @var QueryLogger|null
     */
    protected QueryLogger|null $queryLogger = null;

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
        $this->db()->breadcrumbs(true)
            ->item($this->trans->lang('Tables'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans->lang('Select'));

        // Save select queries options
        $select = $this->db()->getSelectParams($table, $options);
        $this->setSelectBag('options', [
            'limit' => (int)($select->options['limit']['value'] ?? 0),
            'total' => (bool)($options['total'] ?? true), // Keep the same value.
            'length' => (int)($select->options['length']['value'] ?: 100),
        ]);
        $this->stash()->set('select.query', $select->query);

        // Set main menu buttons
        $actions = [
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(InsertFunc::class)->show(true),
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
    protected function header(): string
    {
        return $this->selectUi->header($this->config()->canSaveQuery());
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        return $this->selectUi->content();
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
    // Injecting the query logger here makes it possible to check if the audit db connection is active.
    #[Inject(attr: 'queryLogger')]
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
    #[Databag('dbadmin.tab')]
    public function edit(): void
    {
        $this->cl(QueryEdit::class)->database($this->getSelectRowQuery());
    }
}
