<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

#[Databag('dbadmin.select')]
#[Before('setDefaultSelectOptions')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;
    use SelectBagTrait;

    /**
     * The constructor
     *
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}
}
