<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\MainComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Fields;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Values;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\View;
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
        $this->db->breadcrumbs(true)
            ->item($this->trans()->lang('Views'))
            ->item("<i><b>$table</b></i>")
            ->item($this->trans()->lang('Select'));

        // Save select queries options
        $select = $this->db()->getSelectParams($table, $options);
        $this->setSelectBag('options', [
            'limit' => (int)($select->options['limit']['value'] ?? 0),
            'total' => (bool)($options['total'] ?? true), // Keep the same value.
            'length' => (int)($select->options['length']['value'] ?? 0),
        ]);
        $this->stash()->set('select.query', $select->query);

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
    public function edit(): void
    {
        $this->cl(QueryEdit::class)->database($this->getSelectRowQuery());
    }
}
