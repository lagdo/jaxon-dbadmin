<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\PageComponent as BaseComponent;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 */
abstract class PageComponent extends BaseComponent
{
    use CallableTrait;

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->bag('dbadmin.select')->get('options', [])['limit'] ?? 50;
    }
}
