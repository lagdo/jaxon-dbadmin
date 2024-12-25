<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Command;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

trait ExportTrait
{
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
        $this->db->setCurrentDbName($this->database);

        $exportOptions = $this->db->getExportOptions($this->database);

        // Make data available to views
        $this->view()->shareValues($exportOptions);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $btnId = 'adminer-main-export-submit';
        $formId = 'adminer-main-export-form';
        $databaseNameId = 'adminer-export-database-name';
        $databaseDataId = 'adminer-export-database-data';
        $tableNameId = 'adminer-export-table-name';
        $tableDataId = 'adminer-export-table-data';

        $htmlIds = [
            'btnId' => $btnId,
            'formId' => $formId,
            'databaseNameId' => $databaseNameId,
            'databaseDataId' => $databaseDataId,
            'tableNameId' => $tableNameId,
            'tableDataId' => $tableDataId,
        ];
        return $this->ui->exportPage($htmlIds, $exportOptions['databases'] ?? [],
            $exportOptions['tables'] ?? [], $exportOptions['options'], $exportOptions['labels']);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after()
    {
        $btnId = 'adminer-main-export-submit';
        $formId = 'adminer-main-export-form';
        $databaseNameId = 'adminer-export-database-name';
        $databaseDataId = 'adminer-export-database-data';
        $tableNameId = 'adminer-export-table-name';
        $tableDataId = 'adminer-export-table-data';
        if(($this->database))
        {
            $this->response->js('jaxon.dbadmin')->selectAllCheckboxes($tableNameId);
            $this->response->js('jaxon.dbadmin')->selectAllCheckboxes($tableDataId);
            $this->response->jq("#$btnId")->click($this->rq()->exportOne($this->database, pm()->form($formId)));
            return;
        }

        $this->response->js('jaxon.dbadmin')->selectAllCheckboxes($databaseNameId);
        $this->response->js('jaxon.dbadmin')->selectAllCheckboxes($databaseDataId);
        $this->response->jq("#$btnId")->click($this->rq()->exportSet(pm()->form($formId)));
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $formValues
     *
     * @return Response
     */
    protected function export(array $databases, array $tables, array $formValues): Response
    {
        // Convert checkbox values to boolean
        $formValues['routines'] = isset($formValues['routines']);
        $formValues['events'] = isset($formValues['events']);
        $formValues['autoIncrement'] = isset($formValues['auto_increment']);
        $formValues['triggers'] = isset($formValues['triggers']);

        $results = $this->db->exportDatabases($databases, $tables, $formValues);
        if(\is_string($results))
        {
            // Error
            $this->response->dialog->error($results, 'Error');
            return $this->response;
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
                $this->response->dialog->error('Unable to gzip dump.', 'Error');
                return $this->response;
            }
        }
        $name = '/' . \uniqid() . $extension;
        $path = \rtrim($this->package->getOption('export.dir'), '/') . $name;
        if(!@\file_put_contents($path, $content))
        {
            $this->response->dialog->error('Unable to write dump to file.', 'Error');
            return $this->response;
        }

        $link = \rtrim($this->package->getOption('export.url'), '/') . $name;
        // $this->response->script("window.open('$link', '_blank').focus()");
        $this->response->addCommand('dbadmin.window.open', ['link' => $link]);
        return $this->response;
    }

    /**
     * Export a set of databases on a server
     *
     * @param array $formValues
     *
     * @return Response
     */
    public function exportSet(array $formValues): Response
    {
        $databases = [
            'list' => $formValues['database_list'] ?? [],
            'data' => $formValues['database_data'] ?? [],
        ];
        $tables = [
            'list' => '*',
            'data' => [],
        ];

        return $this->export($databases, $tables, $formValues);
    }

    /**
     * Export one database on a server
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return Response
     */
    public function exportOne(string $database, array $formValues): Response
    {
        $databases = [
            'list' => [$database],
            'data' => [],
        ];
        $tables = [
            'list' => $formValues['table_list'] ?? [],
            'data' => $formValues['table_data'] ?? [],
        ];

        return $this->export($databases, $tables, $formValues);
    }
}
