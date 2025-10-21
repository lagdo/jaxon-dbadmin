<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Table\MainComponent as BaseComponent;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
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
