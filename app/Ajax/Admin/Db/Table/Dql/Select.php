<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

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
use Lagdo\DbAdmin\Support\Service\Admin\AuditDatabase;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use QueryBuilderTrait;

    /**
     * @var AuditDatabase
     */
    #[Inject]
    private AuditDatabase $auditDb;

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getCurrentTable();
        // Set the breadcrumbs
        $this->driver()->breadcrumbs(true)
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
                'handler' => $this->rq(Table::class)->show($table, true),
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
        $canSaveQuery = $this->auditDb->canSaveQuery();
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

        // Run the query.
        $this->cl(ResultSet::class)->page($this->getPageNumber());
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
    public function foreign(string $table, string $column, string|int $value): void
    {
        $this->prependBuilderParams($table, $column, $value);

        $this->render();
    }

    /**
     * Go back to the previous table
     *
     * @param bool $removeLast
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function back(bool $removeLast): void
    {
        if (!$removeLast || $this->removeBuilderParams()) {
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
