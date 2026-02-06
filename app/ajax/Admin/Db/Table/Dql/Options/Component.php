<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;
use Lagdo\DbAdmin\Ui\Select\OptionsUiBuilder;

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
