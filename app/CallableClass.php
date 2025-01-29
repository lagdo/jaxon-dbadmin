<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\CallableClass as JaxonCallableClass;
use Jaxon\App\Dialog\DialogTrait;
use Lagdo\DbAdmin\App\Ui\UiBuilder;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;

/**
 * Callable base class
 *
 * @databag dbadmin
 */
class CallableClass extends JaxonCallableClass
{
    use DialogTrait;
    use CallableTrait;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(private Package $package, private DbFacade $db,
        private UiBuilder $ui, private Translator $trans)
    {}
}
