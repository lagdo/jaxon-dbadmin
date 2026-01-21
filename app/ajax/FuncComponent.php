<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\FuncComponent as JaxonFuncComponent;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Databag('dbadmin')]
class FuncComponent extends JaxonFuncComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param UiBuilder      $ui         The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected UiBuilder $ui, protected Translator $trans)
    {}
}
