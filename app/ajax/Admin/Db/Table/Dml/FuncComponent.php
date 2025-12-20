<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\ComponentTrait;
use Lagdo\DbAdmin\Ajax\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Data\EditUiBuilder;

#[Before('checkDatabaseAccess')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param EditUiBuilder  $editUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected EditUiBuilder $editUi, protected Translator $trans)
    {}
}
