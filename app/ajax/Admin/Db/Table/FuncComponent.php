<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class FuncComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param TableUiBuilder $tableUi   The HTML UI builder
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}
}
