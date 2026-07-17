<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\QueryEditor;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Values;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\MainComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\View;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Support\Service\Admin\AuditConnection;

/**
 * This class provides select query features on tables.
 */
class Select extends MainComponent
{
    use QueryBuilderTrait;

    /**
     * @var AuditConnection
     */
    #[Inject]
    private AuditConnection $audit;

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getCurrentTable();
        // Set the breadcrumbs
        $this->driver()->breadcrumbs(true)
            ->item($this->trans()->lang('Views'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans()->lang('Select'));

        // Save select queries options
        $this->stash()->set('select.query', $this->getBuilderSqlQuery());

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
                'title' => $this->trans()->lang('Views'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        $canSaveQuery = $this->audit->canSaveQuery();
        return $this->selectUi->content($canSaveQuery);
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
    public function show(string $table): void
    {
        $this->initBuilderParams($table);

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
        $this->cl(QueryEditor::class)->database($this->getBuilderSqlQuery());
    }
}
