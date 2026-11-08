<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryResultDto;
use Lagdo\Facades\Logger;
use League\Flysystem\Filesystem;
use Closure;
use DateTime;
use Generator;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function pathinfo;
use function set_time_limit;
use function Jaxon\Storage\storage;

/**
 * Read database contents for export.
 */
class ImportService
{
    /**
     * @param DriverProxy $driver
     */
    public function __construct(private DriverProxy $driver)
    {
        // This is required for the driver setup to complete.
        DriverHelper::setupDriver();
    }

    /**
     * @param string $sOption
     * @param mixed $xDefault
     *
     * @return mixed
     */
    private function uploadOption(string $sOption, mixed $xDefault): mixed
    {
        $sField = 'sql_files';
        $jaxon = jaxon();
        return $jaxon->getAppOption("upload.files.$sField.$sOption",
            $jaxon->getAppOption("upload.default.$sOption", $xDefault));
    }

    /**
     * @return Filesystem|null
     */
    private function storage(): Filesystem|null
    {
        $directory = $this->uploadOption('directory', null);
        if(is_string($directory))
        {
            return storage()->adapter('local')->make($directory);
        }

        $storage = $this->uploadOption('storage', null);
        return is_string($storage) ? storage()->get($storage) : null;
    }

    /**
     * @param string $filename
     * @param int $timeLimit
     *
     * @return ImportOptions|null
     */
    public function options(string $filename, int $timeLimit = -1): ImportOptions|null
    {
        $pathinfo = pathinfo($filename);

        $extension = $pathinfo['extension'];
        if ($extension !== 'sql') {
            Logger::error("Unsupported extension of file $filename.");
            return null;
        }

        $storage = $this->storage();
        $jsonFilename = "{$pathinfo['dirname']}/{$pathinfo['filename']}.json";
        $json = $storage?->read($jsonFilename) ?? null;
        if ($json === null || !is_array($import = json_decode($json, true))) {
            Logger::error("Unable to read file $jsonFilename for database import.");
            return null;
        }

        $logFilename = "{$pathinfo['filename']}_{$pathinfo['dirname']}.log";
        $contentDisposition = 'attachment';
        $contentType = 'text/plain';

        $server = $import['connect']['server'] ?? '';
        $database = $import['connect']['database'] ?? '';
        $schema = $import['connect']['schema'] ?? '';
        $inputs = $import['inputs'];
        $status = $import['status'];

        return new ImportOptions($filename, $extension, $jsonFilename,
            $logFilename, $contentType, $contentDisposition, $server,
            $database, $schema, $storage, $inputs, $status, $timeLimit);
    }

    /**
     * @param ImportOptions $options
     * @param Closure $update
     *
     * @return void
     */
    private function updateStatus(ImportOptions $options, Closure $update): void
    {
        $import = json_decode($options->storage->read($options->jsonFilename), true);
        $import = json_encode($update($import), JSON_PRETTY_PRINT);
        $options->storage->write($options->jsonFilename, $import);
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
     * @param array $count
     *
     * @return void
     */
    public function failed(ImportOptions $options, array $count): void
    {
        $this->updateStatus($options, static function(array $import) use($count) {
            $import['status'] = 'ended';
            $import['result'] = 'fail';
            $import['count'] = $count;
            return $import;
        });
    }

    /**
     * @param ImportOptions $options
     * @param QueryResultDto $result
     *
     * @return void
     */
    private function executed(ImportOptions $options, QueryResultDto $result): void
    {
        $this->updateStatus($options, static function(array $import) use($result) {
            $import['count']['runs'] = $result->queries;
            $import['count']['errors'] = $result->errors;
            return $import;
        });
    }

    /**
     * @param ImportOptions $options
     *
     * @return void
     */
    public function ended(ImportOptions $options): void
    {
        $this->updateStatus($options, static function(array $import) {
            $import['status'] = 'ended';
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
        $timestamp = (new DateTime())->format('Y-m-d H:i:s.v');
        return "$timestamp: $message\n";
    }

    /**
     * @param QueryResultDto $result
     * @param int $total
     *
     * @return string
     */
    private function logStatus(QueryResultDto $result, int $total): string
    {
        $message = "Executed: {$result->queries}/{$total}. Errors: {$result->errors}.";
        return $this->logMessage($message);
    }

    /**
     * @param ImportOptions|null $options
     *
     * @return Generator|null
     */
    public function import(ImportOptions|null $options): Generator|null
    {
        if ($options === null) {
            return null;
        }

        if ($options->timeLimit === 0) {
            set_time_limit(0);
        }

        // Update the status
        $this->starting($options);
        yield $this->logMessage('Starting...');

        $count = $this->driver->prepareImport($options->storage, $options->filename);
        if ($count['errors'] > 0) {
            $message = "Unable to read file {$options->filename} for database import.";
            Logger::error($message);
            // Update the status
            $this->failed($options, $count);
            yield $this->logMessage("Error: $message");
            return;
        }

        // Update the status
        $this->started($options, $count);
        yield $this->logMessage('Running...');

        $this->driver->selectDatabase($options->server, $options->database, $options->schema);

        $queryOptions = new QueryOptions(
            $options->inputs['error_stops'],
            $options->inputs['only_errors']
        );
        $queryOptions->inBatch = true;
        // Update the status every 1 second.
        $durationThreshold = 1;
        $results = $this->driver->executeImport($options->storage,
            $options->filename, $queryOptions);

        $result = null;
        foreach ($results as $_) {
            // The executeImport() function actually returns a generator.
            // But the yielded values are instances of QueryResultDto.
            /** @var QueryResultDto */
            $result = $_;
            if ($result->duration < $durationThreshold) {
                continue;
            }

            $durationThreshold = $result->duration + 1;
            // Update the status
            $this->executed($options, $result);
            yield $this->logStatus($result, $count['total']);
        }

        if ($result !== null) {
            // Update the status
            $this->executed($options, $result);
            yield $this->logStatus($result, $count['total']);
        }

        // Update the status
        $this->ended($options);
        yield $this->logMessage('Ended.');
    }
}
