<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Support\Service\Import\StorageInterface;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<StorageInterface>
 */
class ImportStorage extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return StorageInterface::class;
    }
}
