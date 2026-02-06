<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

#[Databag('dbadmin.select')]
abstract class MainComponent extends BaseComponent
{
    use SelectBagTrait;

    /**
     * The constructor
     *
     * @param SelectUiBuilder   $selectUi   The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}
}
