<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\FuncComponent as BaseFuncComponent;

/**
 * @before checkDatabaseAccess
 */
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;
}
