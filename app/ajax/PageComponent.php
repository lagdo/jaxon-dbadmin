<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\PageComponent as BaseComponent;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Databag('dbadmin')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param UiBuilder      $ui         The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected UiBuilder $ui, protected Translator $trans)
    {}

    /**
     * Render the page and pagination components
     *
     * @param int $pageNumber
     *
     * @return void
     */
    public function page(int $pageNumber = 0): void
    {
        // Get the paginator. This will also set the current page number value.
        $paginator = $this->paginator($pageNumber);
        // Render the page content.
        $this->render();
        // Render the pagination component.
        $paginator->render($this->rq()->page());
    }
}
