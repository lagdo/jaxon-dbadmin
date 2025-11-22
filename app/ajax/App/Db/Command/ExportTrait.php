<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ajax\Exception\ValidationException;
use Lagdo\DbAdmin\Ui\Command\ExportUiBuilder;
use Lagdo\Facades\Logger;

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
     * @param array $formValues
     *
     * @return array
     */
    private function options(array $formValues): array
    {
        // Convert checkbox values to boolean
        $options = [
            'types' => isset($formValues['types']),
            'routines' => isset($formValues['routines']),
            'events' => isset($formValues['events']),
            'autoIncrement' => isset($formValues['auto_increment']),
            'triggers' => isset($formValues['triggers']),
        ];

        foreach($this->db()->getSelectValues() as $name => $values) {
            if(isset($formValues[$name])) {
                $value = trim($formValues[$name]);
                if (!in_array($value, $values)) {
                    $message = $this->trans->lang('The "%s" value is incorrect.', $name);
                    throw new ValidationException($message . ' ' . json_encode($values));
                }
                $options[$name] = $value;
            }
        }

        return $options;
    }

    /**
     * @return string
     */
    public function html(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $options = $this->db()->getExportOptions();
        return $this->exportUi->export($this->rq(), $options);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param array  $databases     The databases to dump
     * @param array  $formValues
     *
     * @return void
     */
    protected function exportDb(array $databases, array $formValues): void
    {
        $options = $this->options($formValues);
        $results = $this->db()->exportDatabases($databases, $options);
        if(is_string($results))
        {
            $this->alert()->title('Error')->error($results);
            return;
        }

        $content = $this->view()->render('dbadmin::views::sql/dump', $results);
        // Dump file
        $output = $options['output'] ?? 'text';
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
        if(!@file_put_contents($path, "$content\n"))
        {
            Logger::debug('Unable to write dump to file.', [
                'path' => $path,
                'content' => $content,
            ]);
            $this->alert()->title('Error')->error('Unable to write dump to file.');
            return;
        }

        $link = rtrim($this->package()->getOption('export.url'), '/') . $name;
        $this->response->jo()->open($link, '_blank')->focus();
    }
}
