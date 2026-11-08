<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

use function Jaxon\storage;

/**
 * Read and write database export files.
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * @return string
     */
    abstract protected function storage(): string;

    /**
     * @param string $filename
     *
     * @return string
     */
    abstract protected function path(string $filename): string;

    /**
     * @return Filesystem
     */
    private function fs(): Filesystem
    {
        return storage()->get($this->storage());
    }

    /**
     * @inheritdoc
     */
    public function read(string $filename): string|null
    {
        try {
            $fs = $this->fs();
            $filepath = $this->path($filename);
            return $fs->fileExists($filepath) ? $fs->read($filepath) : null;
        } catch (FilesystemException|UnableToReadFile) {
            return null;
        }
    }
}
