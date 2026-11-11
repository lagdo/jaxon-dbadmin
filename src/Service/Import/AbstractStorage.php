<?php

namespace Lagdo\DbAdmin\Support\Service\Import;

/**
 * Config, urls and paths for database import files.
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * @inheritDoc
     */
    public function dumps(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function dumpLabels(): array
    {
        return [];
    }
}
