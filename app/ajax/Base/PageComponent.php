<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\PageComponent as BaseComponent;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Databag('dbadmin')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param UiBuilder      $ui         The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
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
        $this->paginate($this->rq()->page(), $pageNumber);
    }
}
