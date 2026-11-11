<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

/**
 * Config, urls and paths for database import files.
 */
interface StorageInterface
{
    /**
     * @return array<string, string>
     */
    public function status(): array;

    /**
     * @param string $filename
     *
     * @return string
     */
    public function path(string $filename): string;

    /**
     * @param string $filename
     *
     * @return string
     */
    public function url(string $filename): string;

    /**
     * @return array<string, array<string, string>>
     */
    public function dumps(): array;

    /**
     * @return array<string, string>
     */
    public function dumpLabels(): array;
}
