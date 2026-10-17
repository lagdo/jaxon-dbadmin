<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Support\Service\Export\DatabaseReader;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<DatabaseReader>
 */
class Export extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return DatabaseReader::class;
    }
}
