<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Base\PageComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * @param TableUiBuilder $tableUi   The HTML UI builder
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return 50;
    }
}
