<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

/**
 * Create a new table
 */
class Create extends TableDdl
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->setCurrentTable('');
        $this->setTableBag('columns', []);
        // The databag entry is created here, so it can be filled by the js script.
        $this->setTableBag('formValues', null);

        // Set main menu buttons
        $tableValues = $this->tableUi->tableFormValues();
        $count = $this->tableUi->columnsFormItemCount();
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq(TableFunc::class)->create($tableValues)
                    ->ifgt($count, 0)
                    ->elseWarning('You need to add at least one column to the table.'),
            ],
            'table-changes' => [
                'title' => $this->trans()->lang('Changes'),
                'handler' => $this->rq(QueryFunc::class)->showCreateChanges($tableValues),
            ],
            'table-queries' => [
                'title' => $this->trans()->lang('Queries'),
                'handler' => $this->rq(QueryFunc::class)->showCreateQueries($tableValues),
            ],
            'table-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Tables::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }
}
