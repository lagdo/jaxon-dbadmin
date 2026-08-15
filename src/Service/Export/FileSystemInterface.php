<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

/**
 * Read and write database export files.
 */
interface FileSystemInterface
{
    /**
     * @param string $content
     * @param string $filename
     *
     * @return string
     */
    public function write(string $content, string $filename): string;

    /**
     * @param string $filename
     *
     * @return string
     */
    public function read(string $filename): string;
}
