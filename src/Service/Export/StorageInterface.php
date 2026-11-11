<?php

namespace Lagdo\DbAdmin\Support\Service\Export;

/**
 * Config, urls and paths for database export files.
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
}
