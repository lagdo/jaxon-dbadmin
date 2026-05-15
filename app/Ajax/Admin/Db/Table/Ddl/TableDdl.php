<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\MainComponent;

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
    protected function header(): array
    {
        return [
            'header' => $this->trans()->lang('Table'),
            'body' => $this->tableUi
                ->metadata($this->metadata())
                ->header(),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function content(): array
    {
        return [
            'body' => $this->tableUi
                ->metadata($this->metadata())
                ->wrapper(),
            'header' => $this->tableUi->wrapperTitle(),
        ];
    }
}
