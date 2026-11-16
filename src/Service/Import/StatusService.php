<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

use Jaxon\App\Ajax\Jaxon;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Storage\FileSystemService;
use Lagdo\DbAdmin\Support\Service\Storage\StatusStorageMixin;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;

use function array_combine;
use function array_filter;
use function array_map;
use function array_values;
use function number_format;

/**
 * Read storage options for import.
 */
class StatusService
{
    use StatusStorageMixin;

    /**
     * @param Jaxon $jaxon
     * @param StorageInterface $storage
     * @param DriverProxy $driver
     * @param FileSystemService $nameService
     */
    public function __construct(private Jaxon $jaxon, private StorageInterface $storage,
        private DriverProxy $driver, private FileSystemService $nameService)
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
     * @return array
     */
    private function dates(): array
    {
        $now = $this->now();
        return [
            'date' => [
                'create' => $now,
                'update' => $now,
            ],
            'duration' => [
                'total' => 0,
                'queries' => 0,
            ],
        ];
    }

    /**
     * @param array $inputs
     *
     * @return array
     */
    private function inputs(array $inputs): array
    {
        return [
            'error_stops' => $inputs['error_stops'] ?? false,
            'only_errors' => $inputs['only_errors'] ?? false,
        ];
    }

    /**
     * @return array
     */
    private function count(): array
    {
        return [
            'total' => 0,
            'runs' => 0,
            'errors' => 0,
        ];
    }

    /**
     * @param array $fileOptions
     * @param array $inputs
     *
     * @return array|null
     */
    public function initStatus(array $fileOptions, array $inputs): array|null
    {
        $filename = $this->nameService->randomName();
        $fileOptions['log'] = "{$filename}_" .
            pathinfo($fileOptions['path'], PATHINFO_FILENAME) . '.log';
        $jsonFilename = "$filename.json";
        $saved = $this->saveStatus($jsonFilename, [
            'status' => 'starting',
            'file' => $fileOptions,
            ...$this->dates(),
            'inputs' => $this->inputs($inputs),
            'connect' => $this->connection(),
            'count' => $this->count(),
        ]);
        // Return the link to the exported file.
        return !$saved ? null : [$filename, $this->storage->url($jsonFilename)];
    }

    /**
     * @return array|null
     */
    public function uploadStorageOptions(): array|null
    {
        return $this->jaxon->getAppOption("upload.files.dump",
            $this->jaxon->getAppOption("upload.default", null));
    }

    /**
     * @param string $storage
     *
     * @return array|null
     */
    public function getDumpStorageOptions(string $storage): array|null
    {
        $options = $this->storage->dumps()[$storage] ?? null;
        return match(true) {
            $options === null => null,
            isset($options['directory']) => ['directory' => $options['directory']],
            isset($options['storage']) => ['storage' => $options['storage']],
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function getStorageLabels(): array
    {
        $makeLabel = fn(array $dump) => !$this->makeStorage($dump) ? null : $dump['label'];
        $storages = array_map($makeLabel, $this->storage->dumps());
        return array_filter($storages, fn($label) => $label !== null);
    }

    /**
     * @param int|null $size
     *
     * @return string
     */
    private function formatSize(int|null $size): string
    {
        return match(true) {
            $size === null => '',
            $size >= 1 << 30 => number_format($size / (1 << 30), 2) . 'GB',
            $size >= 1 << 20 => number_format($size / (1 << 20), 2) . 'MB',
            $size >= 1 << 10 => number_format($size / (1 << 10), 2) . 'KB',
            default => number_format($size) . ' bytes',
        };
    }

    /**
     * @param FileAttributes $dump
     * @param array $labels
     *
     * @return string
     */
    private function makeDumpLabel(FileAttributes $dump, array $labels): string
    {
        $size = $this->formatSize($dump->fileSize());
        $path = $dump->path();
        $label = $labels[$path] ?? $path;
        return $size === '' ? $label : "$label ($size)";
    }

    /**
     * @param array<FileAttributes> $dumps
     *
     * @return array<string, string>
     */
    private function formatDumps(array $dumps): array
    {
        $labels = array_map(fn(FileAttributes $dump) =>
            $this->makeDumpLabel($dump, $this->storage->dumpLabels()), $dumps);

        $paths = array_map(fn(FileAttributes $dump) => $dump->path(), $dumps);
        return array_combine($paths, $labels);
    }

    /**
     * @param string $storage
     *
     * @return Filesystem|null
     */
    private function getDumpStorage(string $storage): Filesystem|null
    {
        $options = $this->getDumpStorageOptions($storage);
        return $options === null ? null : $this->makeStorage($options);
    }

    /**
     * @param string $storage
     *
     * @return array<string, string>|null
     */
    public function getDumps(string $storage): array|null
    {
        if (($storage = $this->getDumpStorage($storage)) === null) {
            return null;
        }

        $dumps = $storage->listContents('/')
            ->filter(fn(StorageAttributes $attributes) => $attributes->isFile())
            ->toArray();
        return $this->formatDumps($dumps);
    }

    /**
     * @param string $storage
     * @param string $dump
     *
     * @return FileAttributes|null
     */
    public function getDump(string $storage, string $dump): FileAttributes|null
    {
        if (($storage = $this->getDumpStorage($storage)) === null) {
            return null;
        }

        $dumps = $storage->listContents('/')
            ->filter(fn(StorageAttributes $attributes) =>
                $attributes->isFile() && $attributes->path() === $dump)
            ->toArray();
        return array_values($dumps)[0] ?? null;
    }

    /**
     * @param FileAttributes $dump
     *
     * @return string
     */
    public function getDumpLabel(FileAttributes $dump): string
    {
        return $this->makeDumpLabel($dump, $this->storage->dumpLabels());
    }
}
