<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\PageComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\UiBuilder;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;

/**
 * @databag dbadmin
 */
abstract class PageComponent extends BaseComponent
{
    use CallableTrait;

    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * @var UiBuilder
     */
    private $ui;

    /**
     * @var Translator
     */
    private $trans;

    /**
     * @var DbFacade
     */
    private $db;

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

    /**
     * Render the page and pagination components
     *
     * @param int $pageNumber
     *
     * @return void
     */
    public function page(int $pageNumber = 0)
    {
        // Get the paginator. This will also set the current page number value.
        $paginator = $this->paginator($pageNumber);
        // Render the page content.
        $this->render();
        // Render the pagination component.
        $paginator->render($this->rq()->page());
    }
}
