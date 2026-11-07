<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Support\Service\Export\ExportService;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<ExportService>
 */
class Export extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return ExportService::class;
    }
}
