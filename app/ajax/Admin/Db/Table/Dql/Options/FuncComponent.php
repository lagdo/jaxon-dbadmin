<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Select\OptionsUiBuilder;

abstract class FuncComponent extends Dql\FuncComponent
{
    /**
     * The constructor
     *
     * @param DbFacade          $db         The facade to database functions
     * @param OptionsUiBuilder  $optionsUi  The HTML UI builder
     * @param Translator        $trans
     */
    public function __construct(protected DbFacade $db,
        protected OptionsUiBuilder $optionsUi, protected Translator $trans)
    {}
}
