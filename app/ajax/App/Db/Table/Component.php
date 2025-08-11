<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\Component as BaseComponent;

/**
 * @before checkDatabaseAccess
 * @after showBreadcrumbs
 */
abstract class Component extends BaseComponent
{
    use ComponentTrait;
}
