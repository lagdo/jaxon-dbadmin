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
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * The facade to database functions
     *
     * @var DbFacade
     */
    private $db;

    /**
     * @var UiBuilder
     */
    private $ui;

    /**
     * @var Translator
     */
    private $trans;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(Package $package, DbFacade $db, UiBuilder $ui, Translator $trans)
    {
        $this->package = $package;
        $this->db = $db;
        $this->ui = $ui;
        $this->trans = $trans;
    }

    /**
     * @return Package
     */
    protected function package(): Package
    {
        return $this->package;
    }

    /**
     * @return DbFacade
     */
    protected function db(): DbFacade
    {
        return $this->db;
    }

    /**
     * @return UiBuilder
     */
    protected function ui(): UiBuilder
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }
}
