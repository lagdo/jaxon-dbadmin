<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Storage\FileNameService;
use Lagdo\DbAdmin\Support\Service\Storage\StatusStorageMixin;

use function in_array;
use function trim;

/**
 * Read storage options for export.
 */
class StatusService
{
    use StatusStorageMixin;

    /**
     * @param StorageInterface $storage
     * @param DriverProxy $driver
     * @param FileNameService $nameService
     */
    public function __construct(private StorageInterface $storage,
        private DriverProxy $driver, private FileNameService $nameService)
    {}

    /**
     * @inheritDoc
     */
    protected function statusOptions(): array
    {
        return $this->storage->status();
    }

    /**
     * @inheritDoc
     */
    protected function statusPath(string $filename): string
    {
        return $this->storage->path($filename);
    }

    /**
     * @param array $inputs
     *
     * @return array|null
     */
    public function checkInputs(array $inputs): array|null
    {
        foreach($this->driver->getExportSelection() as $name => $values) {
            if(isset($inputs[$name])) {
                $value = trim($inputs[$name]);
                if (!in_array($value, $values)) {
                    return [$name, $values];
                }
                $options[$name] = $value;
            }
        }

        return null; // No error.
    }

    /**
     * @param array $inputs
     *
     * @return array
     */
    private function inputs(array $inputs): array
    {
        $booleans = ['types', 'routines', 'events', 'autoIncrement', 'triggers'];
        // Convert checkbox values to boolean
        foreach ($booleans as $boolean) {
            $inputs[$boolean] = isset($inputs[$boolean]);
        }

        return $inputs;
    }

    /**
     * @param array $databases
     * @param array $inputs
     *
     * @return string|null
     */
    public function initStatus(array $databases, array $inputs): string|null
    {
        $extensions = [
            'sql' => '.sql',
            'csv' => '.csv',
            'csv;' => '.csv',
            'tsv' => '.txt',
        ];
        $format = $inputs['format'] ?? 'sql';
        $extension = $extensions[$format] ?? $extensions['sql'];

        $filename = $this->nameService->name();
        $jsonFilename = "$filename.json";
        $saved = $this->saveStatus($jsonFilename, [
            'status' => 'starting',
            'filename' => "$filename$extension",
            'databases' => $databases,
            'inputs' => $this->inputs($inputs),
            'connect' => $this->connection(),
        ]);
        // Return the link to the exported file.
        return !$saved ? null : $this->storage->url($jsonFilename);
    }
}
