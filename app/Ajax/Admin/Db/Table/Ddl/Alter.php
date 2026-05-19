<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

/**
 * Alter a table
 */
class Alter extends TableDdl
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // The databag entry is created here, so it can be filled by the js script.
        $this->setTableBag('formValues', null);

        // Set main menu buttons
        $table = $this->getCurrentTable();
        $tableValues = $this->tableUi->tableFormValues();
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq(TableFunc::class)->alter($tableValues)
                    ->confirm("Save changes on table $table?"),
            ],
            'table-changes' => [
                'title' => $this->trans()->lang('Changes'),
                'handler' => $this->rq(QueryFunc::class)->showAlterChanges($tableValues),
            ],
            'table-queries' => [
                'title' => $this->trans()->lang('Queries'),
                'handler' => $this->rq(QueryFunc::class)->showAlterQueries($tableValues),
            ],
            'table-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }
}
