<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\App\Db\Command\ExportTrait;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

#[Before('setDatabase')]
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
        $this->response->jo('jaxon.dbadmin')
            ->selectAllCheckboxes($this->exportUi->tableNameId);
        $this->response->jo('jaxon.dbadmin')
            ->selectAllCheckboxes($this->exportUi->tableDataId);
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
    #[Before('notYetAvailable')]
    public function export(array $formValues): void
    {
        [, $database] = $this->bag('dbadmin')->get('db');
        $databases = [
            'list' => [$database],
            'data' => [],
        ];
        $tables = [
            'list' => $formValues['table_list'] ?? [],
            'data' => $formValues['table_data'] ?? [],
        ];

        $this->exportDb($databases, $tables, $formValues);
    }
}
