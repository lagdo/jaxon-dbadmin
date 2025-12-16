<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
abstract class MainComponent extends Component
{
    use ComponentTrait;

    /**
     * @var string
     */
    protected $overrides = Content::class;

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
