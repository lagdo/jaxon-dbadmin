<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ExportTrait;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

class Export extends Component
{
    use ExportTrait;

    /**
     * The constructor
     *
     * @param ExportUiBuilder $exportUi The HTML UI builder
     */
    public function __construct(protected ExportUiBuilder $exportUi)
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
        $this->response()->jo('jaxon.dbadmin')
            ->setExportEventHandlers(...$this->exportUi->databaseIds());
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
    public function export(array $formValues): void
    {
        $databases = [];
        foreach ($formValues['database_list'] ?? [] as $database) {
            $databases[$database]['*']['table'] = true;
            $databases[$database]['*']['data'] = false;
        }
        foreach ($formValues['database_data'] ?? [] as $database) {
            if(!isset($databases[$database]['*']['table'])) {
                $databases[$database]['*']['table'] = false;
            }
            $databases[$database]['*']['data'] = true;
        }

        $this->exportDb($databases, $formValues);
    }
}
