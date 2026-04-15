<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\MainComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Select\SelectUiBuilder;

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
