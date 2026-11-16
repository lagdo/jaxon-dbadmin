<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryResultDto;
use Lagdo\DbAdmin\Support\Service\Admin\AuditDatabase;
use Lagdo\DbAdmin\Support\Service\Storage\StorageMixin;
use Lagdo\Facades\Logger;
use Closure;
use DateTime;
use Generator;

use function pathinfo;
use function round;
use function set_time_limit;

/**
 * Read database contents for import.
 */
class ImportService
{
    use StorageMixin;

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
     * @return ImportOptions|null
     */
    public function options(string $jsonFilename, array $options = []): ImportOptions|null
    {
        if (pathinfo($jsonFilename, PATHINFO_EXTENSION) !== 'json') {
            Logger::error("Unsupported extension of file $jsonFilename.");
            return null;
        }
        if (($import = $this->statusService->readStatus($jsonFilename)) === null) {
            Logger::error("Unable to read import status file $jsonFilename.");
            return null;
        }

        $timeLimit = $options['timeLimit'] ?? -1;
        $batchSize = $options['batchSize'] ?? 100;
        $queriesThreshold = $options['queriesThreshold'] ?? 500;
        $durationThreshold = $options['durationThreshold'] ?? 100;

        $connection = $import['connect'];
        $server = $connection['server'] ?? '';
        $database = $connection['database'] ?? '';
        $schema = $connection['schema'] ?? '';
        $this->driver->selectDatabase($server, $database, $schema);
        if (!$this->statusService->verifyHash($connection)) {
            Logger::error("The hash for status file $jsonFilename doesn't match.");
            return null;
        }

        $file = $import['file'];
        $extension = pathinfo($file['path'], PATHINFO_EXTENSION);
        $contentDisposition = 'attachment';
        $contentType = 'text/plain';

        $status = $import['status'];
        $inputs = $import['inputs'];

        return new ImportOptions($file, $extension, $jsonFilename, $contentType,
            $contentDisposition, $server, $database, $schema, $inputs, $status,
            $timeLimit, $batchSize, $queriesThreshold, $durationThreshold);
    }

    /**
     * @param ImportOptions $options
     * @param Closure $update
     *
     * @return void
     */
    private function updateStatus(ImportOptions $options, Closure $update): void
    {
        $import = $this->statusService->readStatus($options->jsonFilename);
        if ($import !== null) {
            $import['date']['update'] = $this->statusService->now();
            $this->statusService->saveStatus($options->jsonFilename, $update($import));
        }
    }

    /**
     * @param ImportOptions $options
     *
     * @return void
     */
    public function starting(ImportOptions $options): void
    {
        $this->updateStatus($options, static function(array $import) {
            $import['status'] = 'preparing';
            return $import;
        });
    }

    /**
     * @param ImportOptions $options
     * @param array $count
     *
     * @return void
     */
    public function started(ImportOptions $options, array $count): void
    {
        $this->updateStatus($options, static function(array $import) use($count) {
            $import['status'] = 'running';
            $import['count'] = $count;
            return $import;
        });
    }

    /**
     * @param ImportOptions $options
     *
     * @return void
     */
    public function failed(ImportOptions $options): void
    {
        $this->updateStatus($options, static function(array $import) {
            $import['status'] = 'completed';
            $import['result'] = 'failure';
            return $import;
        });
    }

    /**
     * @param array $date
     *
     * @return int
     */
    private function totalDuration(array $date): int
    {
        $format = StatusService::DATETIME_FORMAT;
        $createTs = DateTime::createFromFormat($format, $date['create']);
        $updateTs = DateTime::createFromFormat($format, $date['update']);
        $seconds = $updateTs->getTimestamp() - $createTs->getTimestamp();
        $microseconds = (int)$updateTs->format('u') - (int)$createTs->format('u');
        return (int)round(($seconds * 1000) + ($microseconds / 1000));
    }

    /**
     * @param ImportOptions $options
     * @param QueryResultDto $result
     *
     * @return void
     */
    private function executed(ImportOptions $options, QueryResultDto $result): void
    {
        $this->updateStatus($options, function(array $import) use($result) {
            $import['count']['runs'] = $result->queries;
            $import['count']['errors'] = $result->errors;
            $import['duration']['total'] = $this->totalDuration($import['date']);
            $import['duration']['queries'] = (int)($result->duration * 1000);
            return $import;
        });
    }

    /**
     * @param ImportOptions $options
     *
     * @return void
     */
    public function completed(ImportOptions $options): void
    {
        $this->updateStatus($options, static function(array $import) {
            $import['status'] = 'completed';
            $import['result'] = 'success';
            return $import;
        });
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function logMessage(string $message): string
    {
        return $this->statusService->now() . ": $message\n";
    }

    /**
     * @param QueryResultDto $result
     * @param int $total
     *
     * @return string
     */
    private function logStatus(QueryResultDto $result, int $total): string
    {
        $message = "[summary] {$result->queries}/{$total} executed. {$result->errors} failed.";
        return $this->logMessage($message);
    }

    /**
     * @param QueryResultDto $result
     * @param ImportOptions $options
     * @param int $queries
     * @param float $duration
     *
     * @return bool
     */
    private function thresholdReached(QueryResultDto $result,
        ImportOptions $options, int $queries, float $duration): bool
    {
        return $result->queries - $queries > $options->queriesThreshold ||
            ($result->duration - $duration) * 1000 > $options->durationThreshold;
    }

    /**
     * @param ImportOptions $options
     *
     * @return Generator|null
     */
    public function import(ImportOptions $options): Generator|null
    {
        if ($options->timeLimit >= 0) {
            set_time_limit($options->timeLimit);
        }

        // Update the status
        $this->starting($options);
        yield $this->logMessage('[info] Starting import...');

        $storage = $this->makeStorage($options->file);
        $filepath = $options->file['path'];

        $queries = $this->driver->prepareImport($storage, $filepath);
        if ($queries === null) {
            $message = "Unable to read file {$filepath} for database import.";
            Logger::error($message);
            // Update the status
            $this->failed($options);
            yield $this->logMessage("[error] $message");
            return;
        }

        yield $this->logMessage('[info] Preparing import...');

        $total = 0;
        foreach ($queries as $query) {
            if ($query === null) {
                $message = "Invalid or unsupported query found.";
                Logger::error($message);
                // Update the status
                $this->failed($options);
                yield $this->logMessage("[error] $message");
                return;
            }
            // Print this log each 10.000 queries
            if ($total++ % 10_000 === 0) {
                yield $this->logMessage('[info] Preparing...');
            }
        }

        yield $this->logMessage("[info] {$total} queries found.");

        // Update the status
        $this->started($options, ['total' => $total, 'runs' => 0, 'errors' => 0]);
        yield $this->logMessage('[info] Running import...');

        $queryOptions = new QueryOptions(
            $options->inputs['error_stops'],
            $options->inputs['only_errors']
        );
        $queryOptions->inBatch = true;
        $results = $this->driver->executeImport($storage, $filepath, $queryOptions);
        if ($results === null) {
            $message = "Unable to read file {$filepath} for database import.";
            Logger::error($message);
            // Update the status
            $this->failed($options);
            yield $this->logMessage("[error] $message");
            return;
        }

        $queries = 0;
        $duration = 0;
        $result = null;
        foreach ($results as $_) {
            // The executeImport() function actually returns a generator.
            // But the yielded values are instances of QueryResultDto.
            /** @var QueryResultDto */
            $result = $_;
            if ($this->thresholdReached($result, $options, $queries, $duration)) {
                $queries = $result->queries;
                $duration = $result->duration;
                // Update the status
                $this->executed($options, $result);
                yield $this->logStatus($result, $total);
            }
        }

        if ($result !== null && $queries < $result->queries) {
            // Update the status
            $this->executed($options, $result);
            yield $this->logStatus($result, $total);
        }

        // Update the status
        $this->completed($options);
        yield $this->logMessage('[info] Import completed.');
    }
}
