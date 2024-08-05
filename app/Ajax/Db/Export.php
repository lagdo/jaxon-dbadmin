<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

class Export extends CallableDbClass
{
    /**
     * Show the export form
     *
     * @param string $database    The database name
     *
     * @return Response
     */
    protected function showForm(string $database = ''): Response
    {
        // Set the current database, but do not update the databag.
        $this->db->setCurrentDbName($database);

        $exportOptions = $this->db->getExportOptions($database);

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
        $content = $this->ui->exportPage($htmlIds, $exportOptions['databases'] ?? [],
            $exportOptions['tables'] ?? [], $exportOptions['options'], $exportOptions['labels']);
        $this->cl(Content::class)->showHtml($content);

        if(($database))
        {
            $this->response->call("jaxon.dbadmin.selectAllCheckboxes", $tableNameId);
            $this->response->call("jaxon.dbadmin.selectAllCheckboxes", $tableDataId);
            $this->jq("#$btnId")->click($this->rq()->exportOne($database, pm()->form($formId)));
            return $this->response;
        }

        $this->response->call("jaxon.dbadmin.selectAllCheckboxes", $databaseNameId);
        $this->response->call("jaxon.dbadmin.selectAllCheckboxes", $databaseDataId);
        $this->jq("#$btnId")->click($this->rq()->exportSet(pm()->form($formId)));
        return $this->response;
    }

    /**
     * Show the export form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-export', 'adminer-server-actions'])
     *
     * @return Response
     */
    public function showServerForm(): Response
    {
        return $this->showForm();
    }

    /**
     * Show the export form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-export', 'adminer-database-actions'])
     *
     * @return Response
     */
    public function showDatabaseForm(): Response
    {
        [, $database] = $this->bag('dbadmin')->get('db');
        return $this->showForm($database);
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
        $this->response->addCommand(['cmd' => 'dbadmin.window.open'], $link);
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
