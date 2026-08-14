<?php

namespace Lagdo\DbAdmin\Support\Facade;

use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\Facades\AbstractFacade;
use Lagdo\Facades\ServiceInstance;

/**
 * @extends AbstractFacade<AuthInterface>
 */
class Auth extends AbstractFacade
{
    use ServiceInstance;

    /**
     * @inheritDoc
     */
    protected static function getServiceIdentifier(): string
    {
        return AuthInterface::class;
    }
}
