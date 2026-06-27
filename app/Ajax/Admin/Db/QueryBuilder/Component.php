<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;
use Lagdo\DbAdmin\App\Ui\Select\OptionsUiBuilder;

abstract class Component extends Dql\Component
{
    /**
     * @param OptionsUiBuilder  $optionsUi  The HTML UI builder
     */
    public function __construct(protected OptionsUiBuilder $optionsUi)
    {}
}
