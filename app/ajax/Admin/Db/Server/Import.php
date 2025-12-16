<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ImportTrait;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

class Import extends Component
{
    use ImportTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage  $package    The DbAdmin package
     * @param DbFacade        $db         The facade to database functions
     * @param ImportUiBuilder $importUi The HTML UI builder
     * @param Translator      $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected ImportUiBuilder $importUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateServerCommandMenu('server-import');
    }

    /**
     * Show the import form for a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function server(): void
    {
        $this->render();
    }
}
