<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\FuncComponent as JaxonFuncComponent;
use Jaxon\App\Dialog\DialogTrait;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

/**
 * @databag dbadmin
 */
class FuncComponent extends JaxonFuncComponent
{
    use DialogTrait;
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected Package $package, protected DbFacade $db,
        protected UiBuilder $ui, protected Translator $trans)
    {}
}
