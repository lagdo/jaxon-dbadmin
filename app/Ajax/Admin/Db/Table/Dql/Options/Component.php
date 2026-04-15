<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;
use Lagdo\DbAdmin\App\Ui\Select\OptionsUiBuilder;

abstract class Component extends Dql\Component
{
    /**
     * The constructor
     *
     * @param OptionsUiBuilder  $optionsUi  The HTML UI builder
     */
    public function __construct(protected OptionsUiBuilder $optionsUi)
    {}
}
