<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\Facades\Logger;
use Generator;

use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function pathinfo;
use function str_replace;

/**
 * Read database contents for export.
 */
class DatabaseReader
{
    public function __construct(private DriverProxy $driver,
        private FileSystemInterface $fs)
    {
        // This is required for the driver setup to complete.
        DriverHelper::setupDriver();
    }

    /**
     * @param string $filename
     *
     * @return ExportOptions|null
     */
    public function options(string $filename): ExportOptions|null
    {
        $extensions = ['.sql', '.csv', '.txt'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array(".$extension", $extensions, true)) {
            Logger::error("Unsupported extension of file $filename.");
            return null;
        }

        $jsonFilename = str_replace($extensions, '.json', $filename);
        $json = $this->fs->read($jsonFilename);
        if ($json === null || !is_array($options = json_decode($json, true))) {
            Logger::error("Unable to read file $jsonFilename for database export.");
            return null;
        }

        $contentType = match($extension) {
            '.sql' => 'application/sql',
            '.csv' => 'text/csv',
            // '.txt' => 'text/plain',
            default => 'text/plain',
        };
        $output = $options['options']['output'] ?? 'save';
        $contentDisposition = $output === 'open' ? 'inline' : 'attachment';

        $server = $options['connect']['server'] ?? '';
        $database = $options['connect']['database'] ?? '';
        $schema = $options['connect']['schema'] ?? '';
        $databases = $options['databases'];
        $options = $options['options'];

        return new ExportOptions($filename, $extension, $jsonFilename, $contentType,
            $contentDisposition, $server, $database, $schema, $databases, $options);
    }

    /**
     * @param ExportOptions $options
     *
     * @return Generator|null
     */
    public function export(ExportOptions $options): Generator|null
    {
        $this->driver->selectDatabase($options->server, $options->database, $options->schema);
        $results = $this->driver->exportDatabases($options->databases, $options->options);
        if (!is_string($results)) {
            return $results;
        }

        Logger::error("Unable to read file {$options->filename} for database export.", [
            'message' => $results,
        ]);
        return null;
    }
}
