<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\Facades\Logger;
use Generator;

use function deflate_add;
use function deflate_init;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function pathinfo;
use function strlen;
use function substr;

/**
 * Read database contents for export.
 */
class DatabaseReader
{
    /**
     * @param DriverProxy $driver
     * @param FileSystemInterface $fs
     */
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
        $extensions = ['sql', 'csv', 'txt'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array($extension, $extensions, true)) {
            Logger::error("Unsupported extension of file $filename.");
            return null;
        }

        $jsonFilename = substr($filename, 0, strlen($filename) - 3) . 'json';
        $json = $this->fs->read($jsonFilename);
        if ($json === null || !is_array($options = json_decode($json, true))) {
            Logger::error("Unable to read file $jsonFilename for database export.");
            return null;
        }

        $output = $options['options']['output'] ?? 'save';
        $contentDisposition = $output === 'open' ? 'inline' : 'attachment';
        $contentType = match(true) {
            $output === 'gzip' => 'application/gzip',
            $output === 'open' => 'text/plain',
            $extension === 'sql' => 'application/sql',
            $extension === 'csv' => 'text/csv',
            default => 'text/plain',
        };

        $server = $options['connect']['server'] ?? '';
        $database = $options['connect']['database'] ?? '';
        $schema = $options['connect']['schema'] ?? '';
        $databases = $options['databases'];
        $options = $options['options'];

        if ($options['output'] === 'gzip') {
            $filename .= '.gz';
        }

        return new ExportOptions($filename, $extension, $jsonFilename, $contentType,
            $contentDisposition, $server, $database, $schema, $databases, $options);
    }

    /**
     * @param Generator $results
     *
     * @return Generator
     */
    private function compress(Generator $results, int $level = 6): Generator
    {
        $context = deflate_init(ZLIB_ENCODING_GZIP, ['level' => $level]);
        foreach ($results as $result) {
            $compressed = deflate_add($context, $result, ZLIB_NO_FLUSH);
            if ($compressed !== '') {
                yield $compressed;
            }
        }
        yield deflate_add($context, '', ZLIB_FINISH);
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
            return $options->options['output'] !== 'gzip' ?
                $results : $this->compress($results);
        }

        Logger::error("Unable to read file {$options->filename} for database export.", [
            'message' => $results,
        ]);
        return null;
    }
}
