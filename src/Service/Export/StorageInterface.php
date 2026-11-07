<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

/**
 * Read and write database export files.
 */
interface StorageInterface
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
     * @return string|null
     */
    public function read(string $filename): string|null;
}
