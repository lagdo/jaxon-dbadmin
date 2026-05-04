<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

/**
 * Alter a table
 */
#[After('showBreadcrumbs')]
#[Export(['render'])]
class Alter extends MainComponent
{
    /**
     * The database table data.
     *
     * @var array|null
     */
    private $metadata = null;

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableMetadata($this->getCurrentTable());
    }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // Set main menu buttons
        $table = $this->getCurrentTable();
        $values = $this->tableUi->listFormValues();
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq(TableFunc::class)->alter($values)
                    ->confirm("Save changes on table $table?"),
            ],
            'table-changes' => [
                'title' => $this->trans()->lang('Changes'),
                'handler' => $this->rq(QueryFunc::class)->changes($values),
            ],
            'table-queries' => [
                'title' => $this->trans()->lang('Queries'),
                'handler' => $this->rq(QueryFunc::class)->alterTable($values),
            ],
            'table-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    protected function header(): string
    {
        return $this->tableUi
            ->metadata($this->metadata())
            ->header();
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        return $this->tableUi
            ->metadata($this->metadata())
            ->wrapper();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Column\Wrapper::class)->load($this->metadata());
    }
}
