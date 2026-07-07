<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\QueryEditor;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Values;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\InsertFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Support\Service\Admin\QueryLogger;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use ComponentDataTrait;
    use QueryBuilderTrait;

    /**
     * @var QueryLogger|null
     */
    protected QueryLogger|null $queryLogger = null;

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getCurrentTable();
        // Set the breadcrumbs
        $this->db()->breadcrumbs(true)
            ->item($this->trans->lang('Tables'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans->lang('Select'));

        // Save select queries options
        $this->stash()->set('select.query', $this->getBuilderSqlQuery());

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
                'title' => $this->trans()->lang('Tables'),
                'handler' => $this->rq(Tables::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        $canSaveQuery = $this->config()->canSaveQuery();
        $canGoBack = $this->countBuilderParams() > 1;
        return $this->selectUi->content($canSaveQuery, $canGoBack);
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
        // Also run the query.
        $this->cl(ResultSet::class)->page($this->get('page', 1));
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
        $this->initBuilderParams($table);

        $this->render();
    }

    /**
     * Show a table following a foreign key
     *
     * @param string $table       The table name
     * @param string $column      The foreign column
     * @param string $value       The foreign $value
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    // Injecting the query logger here makes it possible to check if the audit db connection is active.
    #[Inject(attr: 'queryLogger')]
    public function follow(string $table, string $column, string|int $value): void
    {
        $this->prependBuilderParams($table, $column, $value);

        $this->render();
    }

    /**
     * Go back to the previous table
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    // Injecting the query logger here makes it possible to check if the audit db connection is active.
    #[Inject(attr: 'queryLogger')]
    public function back(): void
    {
        if ($this->removeBuilderParams()) {
            $this->render();
        }
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
        $this->cl(QueryEditor::class)->database($this->getBuilderSqlQuery());
    }
}
