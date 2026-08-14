<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Driver\Utils\TranslatorInterface;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<TranslatorInterface>
 */
class Translator extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return TranslatorInterface::class;
    }
}
