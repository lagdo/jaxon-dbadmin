<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Base\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
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
