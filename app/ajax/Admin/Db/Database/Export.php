<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ExportTrait;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

#[Before('setDatabase')]
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

    protected function setDatabase(): void
    {
        [, $database] = $this->getCurrentDb();
        $this->db()->setCurrentDbName($database);
    }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseCommandMenu('database-export');
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->response()->jo('jaxon.dbadmin')
            ->setExportEventHandlers(...$this->exportUi->tableIds());
    }

    /**
     * Show the export form for a database
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function database(): void
    {
        $this->render();
    }

    /**
     * Export one database on a server
     *
     * @param array $formValues
     *
     * @return void
     */
    public function export(array $formValues): void
    {
        [, $database] = $this->getCurrentDb();
        $databases = [
            $database =>  [],
        ];
        foreach ($formValues['table_list'] ?? [] as $table) {
            $databases[$database][$table]['table'] = true;
            $databases[$database][$table]['data'] = false;
        }
        foreach ($formValues['table_data'] ?? [] as $table) {
            if(!isset($databases[$database][$table]['table'])) {
                $databases[$database][$table]['table'] = false;
            }
            $databases[$database][$table]['data'] = true;
        }

        $this->exportDb($databases, $formValues);
    }
}
