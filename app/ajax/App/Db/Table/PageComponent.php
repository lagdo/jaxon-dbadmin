<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\PageComponent as BaseComponent;

/**
 * @before checkDatabaseAccess
 */
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return 50;
    }
}
