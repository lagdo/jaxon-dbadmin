<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\FuncComponent as JaxonFuncComponent;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Databag('dbadmin')]
class FuncComponent extends JaxonFuncComponent
{
    use ComponentTrait;

    /**
     * @var ServerConfig
     */
    #[Inject]
    protected ServerConfig $config;

    /**
     * @var DbFacade
     */
    #[Inject]
    protected DbFacade $db;

    /**
     * @var Translator
     */
    #[Inject]
    protected Translator $trans;

    /**
     * @var UiBuilder
     */
    #[Inject]
    protected UiBuilder $ui;
}
