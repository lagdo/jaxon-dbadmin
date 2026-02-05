<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\PageComponent as BaseComponent;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Databag('dbadmin')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;
    use TabItemTrait;

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
