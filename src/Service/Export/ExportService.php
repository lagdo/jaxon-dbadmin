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
use function set_time_limit;
use function strlen;

/**
 * Read database contents for export.
 */
class ExportService
{
    /**
     * @param DriverProxy $driver
     * @param StorageInterface $fs
     */
    public function __construct(private DriverProxy $driver,
        private StorageInterface $fs)
    {
        // This is required for the driver setup to complete.
        DriverHelper::setupDriver();
    }

    /**
     * @param string $filename
     * @param int $bufferLimit
     * @param int $timeLimit
     *
     * @return ExportOptions|null
     */
    public function options(string $filename, int $bufferLimit = 32768,
        int $timeLimit = -1): ExportOptions|null
    {
        $pathinfo = pathinfo($filename);

        $extension = $pathinfo['extension'];
        if (!in_array($extension, ['sql', 'csv', 'txt'], true)) {
            Logger::error("Unsupported extension of file $filename.");
            return null;
        }

        $jsonFilename = "{$pathinfo['dirname']}/{$pathinfo['filename']}.json";
        $json = $this->fs->read($jsonFilename);
        if ($json === null || !is_array($export = json_decode($json, true))) {
            Logger::error("Unable to read file $jsonFilename for database export.");
            return null;
        }

        $output = $export['options']['output'] ?? 'save';
        $contentDisposition = $output === 'open' ? 'inline' : 'attachment';
        $contentType = match(true) {
            $output === 'gzip' => 'application/gzip',
            $output === 'open' => 'text/plain',
            $extension === 'sql' => 'application/sql',
            $extension === 'csv' => 'text/csv',
            default => 'text/plain',
        };

        $server = $export['connect']['server'] ?? '';
        $database = $export['connect']['database'] ?? '';
        $schema = $export['connect']['schema'] ?? '';
        $databases = $export['databases'];
        $options = $export['options'];

        if ($options['output'] === 'gzip') {
            $filename .= '.gz';
        }

        return new ExportOptions($filename, $extension, $jsonFilename,
            $contentType, $contentDisposition, $server, $database,
            $schema, $databases, $options, $bufferLimit, $timeLimit);
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
     * @param Generator $results
     * @param ExportOptions $options
     *
     * @return Generator
     */
    private function bufferize(Generator $results, ExportOptions $options): Generator
    {
        $buffer = '';
        foreach ($results as $result) {
            $buffer .= $result;
            if (strlen($buffer) >= $options->bufferLimit) {
                yield $buffer;

                $buffer = '';
                if ($options->timeLimit > 0) {
                    set_time_limit($options->timeLimit);
                }
            }
        }
        if ($buffer !== '') {
            yield $buffer;
        }
    }

    /**
     * @param ExportOptions|null $options
     *
     * @return Generator|null
     */
    public function export(ExportOptions|null $options): Generator|null
    {
        if ($options === null) {
            return null;
        }

        if ($options->timeLimit === 0) {
            set_time_limit(0);
        }

        $this->driver->selectDatabase($options->server, $options->database, $options->schema);
        $results = $this->driver->exportDatabases($options->databases, $options->options);
        if (!is_string($results)) {
            if ($options->options['output'] === 'gzip') {
                $results = $this->compress($results);
            }
            return $this->bufferize($results, $options);
        }

        Logger::error("Unable to read file {$options->filename} for database export.", [
            'message' => $results,
        ]);
        return null;
    }
}
