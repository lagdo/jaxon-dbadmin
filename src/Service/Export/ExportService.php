<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Admin\AuditDatabase;
use Lagdo\Facades\Logger;
use Generator;

use function deflate_add;
use function deflate_init;
use function in_array;
use function is_string;
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
     * @param StatusService $statusService
     * @param AuditDatabase $auditDb
     */
    public function __construct(private DriverProxy $driver,
        private StatusService $statusService, AuditDatabase $auditDb)
    {
        // This is required for the driver setup to complete.
        DriverHelper::setupDriver();
        // Make sure the connection to the audit database is closed.
        $auditDb->closeConnection();
    }

    /**
     * @param string $jsonFilename
     * @param array $options
     *
     * @return ExportOptions|null
     */
    public function options(string $jsonFilename, array $options): ExportOptions|null
    {
        if (pathinfo($jsonFilename, PATHINFO_EXTENSION) !== 'json') {
            Logger::error("Unsupported extension of file $jsonFilename.");
            return null;
        }
        if (($export = $this->statusService->readStatus($jsonFilename)) === null) {
            Logger::error("Unable to read export status file $jsonFilename.");
            return null;
        }

        $timeLimit = $options['timeLimit'] ?? -1;
        $bufferLimit = $options['bufferLimit'] ?? 32768;

        $connection = $export['connect'];
        $server = $connection['server'] ?? '';
        $database = $connection['database'] ?? '';
        $schema = $connection['schema'] ?? '';
        $this->driver->selectDatabase($server, $database, $schema);
        if (!$this->statusService->verifyHash($connection)) {
            Logger::error("The hash for status file $jsonFilename doesn't match.");
            return null;
        }

        $filename = $export['filename'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array($extension, ['sql', 'csv', 'txt'], true)) {
            Logger::error("Unsupported extension of file {$filename}.");
            return null;
        }

        $output = $export['inputs']['output'] ?? 'save';
        $contentDisposition = $output === 'open' ? 'inline' : 'attachment';
        $contentType = match(true) {
            $output === 'gzip' => 'application/gzip',
            $output === 'open' => 'text/plain',
            $extension === 'sql' => 'application/sql',
            $extension === 'csv' => 'text/csv',
            default => 'text/plain',
        };

        $databases = $export['databases'];
        $inputs = $export['inputs'];

        if ($inputs['output'] === 'gzip') {
            $filename .= '.gz';
        }

        return new ExportOptions($filename, $extension, $jsonFilename,
            $contentType, $contentDisposition, $server, $database,
            $schema, $databases, $inputs, $bufferLimit, $timeLimit);
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
     * @param ExportOptions $options
     *
     * @return Generator|null
     */
    public function export(ExportOptions $options): Generator|null
    {
        if ($options->timeLimit >= 0) {
            set_time_limit($options->timeLimit);
        }

        $this->driver->selectDatabase($options->server, $options->database, $options->schema);
        $results = $this->driver->exportDatabases($options->databases, $options->inputs);
        if (!is_string($results)) {
            if ($options->inputs['output'] === 'gzip') {
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
