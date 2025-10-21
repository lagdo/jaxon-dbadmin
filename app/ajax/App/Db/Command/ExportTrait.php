<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

use function Jaxon\je;

trait ExportTrait
{
    /**
     * @var ExportUiBuilder
     */
    protected ExportUiBuilder $exportUi;

    /**
     * @var string
     */
    private $database = '';

    /**
     * @return string
     */
    public function html(): string
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);

        $exportOptions = $this->db()->getExportOptions($this->database);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $btnId = 'dbadmin-main-export-submit';
        $formId = 'dbadmin-main-export-form';
        $databaseNameId = 'dbadmin-export-database-name';
        $databaseDataId = 'dbadmin-export-database-data';
        $tableNameId = 'dbadmin-export-table-name';
        $tableDataId = 'dbadmin-export-table-data';

        $htmlIds = [
            'btnId' => $btnId,
            'formId' => $formId,
            'databaseNameId' => $databaseNameId,
            'databaseDataId' => $databaseDataId,
            'tableNameId' => $tableNameId,
            'tableDataId' => $tableDataId,
        ];
        return $this->exportUi->page($htmlIds, $exportOptions['databases'] ?? [],
            $exportOptions['tables'] ?? [], $exportOptions['options'], $exportOptions['labels']);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        $btnId = 'dbadmin-main-export-submit';
        $formId = 'dbadmin-main-export-form';
        $databaseNameId = 'dbadmin-export-database-name';
        $databaseDataId = 'dbadmin-export-database-data';
        $tableNameId = 'dbadmin-export-table-name';
        $tableDataId = 'dbadmin-export-table-data';
        if(($this->database))
        {
            $this->response->jo('jaxon.dbadmin')->selectAllCheckboxes($tableNameId);
            $this->response->jo('jaxon.dbadmin')->selectAllCheckboxes($tableDataId);
            $this->response->jq("#$btnId")->click($this->rq()->exportOne($this->database, je($formId)->rd()->form()));
            return;
        }

        $this->response->jo('jaxon.dbadmin')->selectAllCheckboxes($databaseNameId);
        $this->response->jo('jaxon.dbadmin')->selectAllCheckboxes($databaseDataId);
        $this->response->jq("#$btnId")->click($this->rq()->exportSet(je($formId)->rd()->form()));
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $formValues
     *
     * @return void
     */
    protected function export(array $databases, array $tables, array $formValues): void
    {
        // Convert checkbox values to boolean
        $formValues['routines'] = isset($formValues['routines']);
        $formValues['events'] = isset($formValues['events']);
        $formValues['autoIncrement'] = isset($formValues['auto_increment']);
        $formValues['triggers'] = isset($formValues['triggers']);

        $results = $this->db()->exportDatabases($databases, $tables, $formValues);
        if(\is_string($results))
        {
            // Error
            $this->alert()->title('Error')->error($results);
            return;
        }

        $content = $this->view()->render('adminer::views::sql/dump', $results);
        // Dump file
        $output = $formValues['output'] ?? 'text';
        $extension = $output === 'gz' ? '.gz' : ($output === 'file' ? '.sql' : '.txt');
        if($output === 'gz')
        {
            // Zip content
            $content = \gzencode($content);
            if(!$content)
            {
                $this->alert()->title('Error')->error('Unable to gzip dump.');
                return;
            }
        }
        $name = '/' . \uniqid() . $extension;
        $path = \rtrim($this->package()->getOption('export.dir'), '/') . $name;
        if(!@\file_put_contents($path, $content))
        {
            $this->alert()->title('Error')->error('Unable to write dump to file.');
            return;
        }

        $link = \rtrim($this->package()->getOption('export.url'), '/') . $name;
        // $this->response->script("window.open('$link', '_blank').focus()");
        $this->response->jo()->open($link, '_blank')->focus();
    }

    /**
     * Export a set of databases on a server
     *
     * @param array $formValues
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function exportSet(array $formValues): void
    {
        $databases = [
            'list' => $formValues['database_list'] ?? [],
            'data' => $formValues['database_data'] ?? [],
        ];
        $tables = [
            'list' => '*',
            'data' => [],
        ];

        $this->export($databases, $tables, $formValues);
    }

    /**
     * Export one database on a server
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function exportOne(string $database, array $formValues): void
    {
        $databases = [
            'list' => [$database],
            'data' => [],
        ];
        $tables = [
            'list' => $formValues['table_list'] ?? [],
            'data' => $formValues['table_data'] ?? [],
        ];

        $this->export($databases, $tables, $formValues);
    }
}
