<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Support\Service\Import\ImportService;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<ImportService>
 */
class Import extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return ImportService::class;
    }
}
