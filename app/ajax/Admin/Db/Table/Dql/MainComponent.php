<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\SelectUiBuilder;

#[Databag('dbadmin.select')]
abstract class MainComponent extends BaseComponent
{
    /**
     * The constructor
     *
     * @param DbFacade      $db         The facade to database functions
     * @param SelectUiBuilder $selectUi The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected DbFacade $db,
        protected SelectUiBuilder $selectUi, protected Translator $trans)
    {}
}
