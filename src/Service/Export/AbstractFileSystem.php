<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

use function Jaxon\storage;

/**
 * Read and write database export files.
 */
abstract class AbstractFileSystem implements FileSystemInterface
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
     * @param string $filename
     *
     * @return string
     */
    abstract protected function url(string $filename): string;

    /**
     * @return Filesystem
     */
    private function fs(): Filesystem
    {
        return storage()->get($this->storage());
    }

    /**
     * @param string $content
     * @param string $filename
     *
     * @return string
     */
    public function write(string $content, string $filename): string
    {
        try {
            $this->fs()->write($this->path($filename), "$content\n");
        } catch (FilesystemException|UnableToWriteFile) {
            return '';
        }
        // Return the link to the exported file.
        return $this->url($filename);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function read(string $filename): string
    {
        try {
            $fs = $this->fs();
            $filepath = $this->path($filename);
            return !$fs->fileExists($filepath) ?
                "No file $filename found." : $fs->read($filepath);
        } catch (FilesystemException|UnableToReadFile) {
            return "No file $filename found.";
        }
    }
}
