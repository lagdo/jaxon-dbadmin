<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Base\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param TableUiBuilder $tableUi   The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected TableUiBuilder $tableUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return 50;
    }
}
