<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Component as BaseComponent;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
abstract class Component extends BaseComponent
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
}
