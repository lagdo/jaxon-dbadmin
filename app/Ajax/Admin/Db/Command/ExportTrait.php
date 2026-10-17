<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ajax\Exception\ValidationException;
use Lagdo\DbAdmin\App\Ui\Command\ExportUiBuilder;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Facade\FileSystem;
use Lagdo\Facades\Logger;

use function in_array;
use function json_encode;
use function str_replace;
use function trim;
use function uniqid;

trait ExportTrait
{
    /**
     * @var ExportUiBuilder
     */
    protected ExportUiBuilder $exportUi;

    /**
     * @return DriverProxy
     */
    abstract protected function driver(): DriverProxy;

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

        foreach($this->driver()->getExportSelection() as $name => $values) {
            if(isset($formValues[$name])) {
                $value = trim($formValues[$name]);
                if (!in_array($value, $values)) {
                    $message = $this->trans()->lang('The "%s" value is incorrect.', $name);
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
    protected function content(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $options = $this->driver()->getExportOptions();
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
        $fs = FileSystem::instance();
        if (!$fs) {
            $this->alert()->title('Error')
                ->error('The export feature is not setup.');
            return;
        }

        $options = $this->options($formValues);
        $extensions = [
            'sql' => '.sql',
            'csv' => '.csv',
            'csv;' => '.csv',
            'tsv' => '.txt',
        ];
        $format = $options['format'] ?? 'sql';

        $currentDb = $this->driver()->currentDb();
        $jsonContent = json_encode([
            'connect' => [
                'server' => $currentDb->server,
                'database' => $currentDb->name,
                'schema' => $currentDb->schema,
            ],
            'databases' => $databases,
            'options' => $options,
        ], JSON_PRETTY_PRINT);

        $fileId = uniqid();
        $jsonFilename = "{$fileId}.json";
        $exportUrl = $fs->write($jsonContent, $jsonFilename);
        if ($exportUrl === '') {
            Logger::debug('Unable to write export to temp file.', [
                'filename' => $jsonFilename,
                'options' => $options,
            ]);
            $this->alert()->title('Error')->error('Unable to write temp file.');
            return;
        }

        $filename = $fileId . $extensions[$format] ?? $extensions['sql'];
        $exportUrl = str_replace($jsonFilename, $filename, $exportUrl);
        $output = $options['output'] ?? 'save';
        $output === 'open' ?
            $this->response()->jo()->open($exportUrl, '_blank')->focus() :
            $this->response()->jo('jaxon.dbadmin')->downloadFile($exportUrl, $filename);
    }
}
