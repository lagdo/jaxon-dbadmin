<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ImportTrait;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

class Import extends Component
{
    use ImportTrait;

    /**
     * The constructor
     *
     * @param ImportUiBuilder $importUi The HTML UI builder
     */
    public function __construct(protected ImportUiBuilder $importUi)
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
