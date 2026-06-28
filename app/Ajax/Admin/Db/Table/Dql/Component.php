<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Select\SelectUiBuilder;

#[Databag('dbadmin.builder')]
abstract class Component extends BaseComponent
{
    use QueryBuilderTrait;

    /**
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}
}
