<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\App\Ui\Select\SelectUiBuilder;

#[Databag('dbadmin.builder')]
#[Before('setDefaultSelectOptions')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;
    use QueryBuilderTrait;

    /**
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}
}
