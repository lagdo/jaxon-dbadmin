<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

/**
 * Read and write database export files.
 */
interface StorageInterface
{
    /**
     * @param string $filename
     *
     * @return string
     */
    public function url(string $filename): string;

    /**
     * @param string $filename
     *
     * @return string|null
     */
    public function read(string $filename): string|null;
}
