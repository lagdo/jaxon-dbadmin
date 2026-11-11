<?php

namespace Lagdo\DbAdmin\Support\Service\Storage;

use League\Flysystem\Filesystem;

use function is_string;
use function Jaxon\storage;

trait StorageMixin
{
    /**
     * @param array $options
     *
     * @return Filesystem|null
     */
    private function makeStorage(array $options): Filesystem|null
    {
        $directory = $options['directory'] ?? null;
        if(is_string($directory))
        {
            return storage()->adapter('local')->make($directory);
        }

        $storage = $options['storage'] ?? null;
        return is_string($storage) ? storage()->get($storage) : null;
    }
}
