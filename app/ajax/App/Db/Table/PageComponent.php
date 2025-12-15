<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param TableUiBuilder $tableUi   The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
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
