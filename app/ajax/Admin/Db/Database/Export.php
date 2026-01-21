<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ExportTrait;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

#[Before('setDatabase')]
class Export extends Component
{
    use ExportTrait;

    /**
     * The constructor
     *
     * @param ServerConfig    $config     The package config
     * @param DbFacade        $db         The facade to database functions
     * @param ExportUiBuilder $exportUi The HTML UI builder
     * @param Translator      $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected ExportUiBuilder $exportUi, protected Translator $trans)
    {}

    protected function setDatabase(): void
    {
        [, $database] = $this->bag('dbadmin')->get('db');
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
            ->setExportEventHandlers($this->exportUi->tableNameId);
        $this->response()->jo('jaxon.dbadmin')
            ->setExportEventHandlers($this->exportUi->tableDataId);
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
        [, $database] = $this->bag('dbadmin')->get('db');
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
