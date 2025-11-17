<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\App\Db\Command\ExportTrait;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

class Export extends Component
{
    use ExportTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage  $package    The DbAdmin package
     * @param DbFacade        $db         The facade to database functions
     * @param ExportUiBuilder $exportUi The HTML UI builder
     * @param Translator      $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected ExportUiBuilder $exportUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateServerCommandMenu('server-export');
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->response->jo('jaxon.dbadmin')
            ->selectAllCheckboxes($this->exportUi->databaseNameId);
        $this->response->jo('jaxon.dbadmin')
            ->selectAllCheckboxes($this->exportUi->databaseDataId);
    }

    /**
     * Show the export form for a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function server(): void
    {
        $this->render();
    }

    /**
     * Export a set of databases on a server
     *
     * @param array $formValues
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function export(array $formValues): void
    {
        $databases = [
            'list' => $formValues['database_list'] ?? [],
            'data' => $formValues['database_data'] ?? [],
        ];
        $tables = [
            'list' => '*',
            'data' => [],
        ];

        $this->exportDb($databases, $tables, $formValues);
    }
}
