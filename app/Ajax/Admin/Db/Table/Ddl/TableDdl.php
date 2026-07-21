<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;

/**
 * Base class for create and alter operations on tables.
 */
#[After('showBreadcrumbs')]
#[Export(['render'])]
abstract class TableDdl extends MainComponent
{
    /**
     * The database table data.
     *
     * @var TableFormDto|null
     */
    private TableFormDto|null $metadata = null;

    /**
     * @return TableFormDto
     */
    protected function metadata(): TableFormDto
    {
        return $this->metadata ??= $this->driver()->getTableMetadata($this->getCurrentTable());
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        return $this->tableUi
            ->metadata($this->metadata())
            ->content();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Column\Wrapper::class)->load($this->metadata());
    }
}
