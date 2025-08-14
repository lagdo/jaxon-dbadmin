<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Query as QueryEdit;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

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
     * The select query div id
     *
     * @var string
     */
    private $txtQueryId = 'dbadmin-table-select-query';

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // The columns, filters and sorting values are reset.
        $this->bag('dbadmin.select')->set('columns', []);
        $this->bag('dbadmin.select')->set('filters', []);
        $this->bag('dbadmin.select')->set('sorting', []);
        // While the options values are kept.
        $options = $this->bag('dbadmin.select')->get('options', []);

        $table = $this->getTableName();

        // Save select queries options
        $selectData = $this->db()->getSelectData($table, $options);
        $this->bag('dbadmin.select')->set('options', [
            'limit' => (int)($selectData->options['limit']['value'] ?? 0),
            'length' => (int)($selectData->options['length']['value'] ?? 0),
        ]);
        $this->stash()->set('select.query', $selectData->query);

        // Set main menu buttons
        $actions = [
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(Insert::class)->show(),
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
        $ids = [
            'formId' => $this->formOptionsId,
            'txtQueryId' => $this->txtQueryId,
        ];
        return $this->selectUi->table($ids);
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
     * @after showBreadcrumbs
     *
     * @param string $table       The table name
     * @param bool $backToTables
     *
     * @return void
     */
    public function show(string $table, bool $backToTables = false): void
    {
        // Save the table name in the databag.
        $this->bag('dbadmin')->set('db.table.name', $table);
        // Save the current page in the databag
        $this->savePageNumber(1);

        $this->render();
    }

    /**
     * Edit the current select query
     *
     * @return void
     */
    public function edit(): void
    {
        $this->cl(QueryEdit::class)->database($this->getSelectQuery());
    }
}
