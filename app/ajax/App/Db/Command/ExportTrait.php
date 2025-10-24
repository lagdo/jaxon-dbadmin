<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;

use function file_put_contents;
use function gzencode;
use function is_string;
use function rtrim;
use function uniqid;

trait ExportTrait
{
    /**
     * @var ExportUiBuilder
     */
    protected ExportUiBuilder $exportUi;

    /**
     * @return string
     */
    public function html(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $exportOptions = $this->db()->getExportOptions();
        return $this->exportUi->export($this->rq(), $exportOptions);
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
    protected function exportDb(array $databases, array $tables, array $formValues): void
    {
        // Convert checkbox values to boolean
        $formValues['routines'] = isset($formValues['routines']);
        $formValues['events'] = isset($formValues['events']);
        $formValues['autoIncrement'] = isset($formValues['auto_increment']);
        $formValues['triggers'] = isset($formValues['triggers']);
        $results = $this->db()->exportDatabases($databases, $tables, $formValues);
        if(is_string($results))
        {
            $this->alert()->title('Error')->error($results);
            return;
        }

        $content = $this->view()->render('adminer::views::sql/dump', $results);
        // Dump file
        $output = $formValues['output'] ?? 'text';
        if($output === 'gz')
        {
            // Zip content
            if(!($content = gzencode($content)))
            {
                $this->alert()->title('Error')->error('Unable to gzip dump.');
                return;
            }
        }

        $name = '/' . uniqid() . match($output) {
            'gz' => '.gz',
            'file' => '.sql',
            default => '.txt',
        };
        $path = rtrim($this->package()->getOption('export.dir'), '/') . $name;
        if(!@file_put_contents($path, $content))
        {
            $this->alert()->title('Error')->error('Unable to write dump to file.');
            return;
        }

        $link = rtrim($this->package()->getOption('export.url'), '/') . $name;
        $this->response->jo()->open($link, '_blank')->focus();
    }
}
