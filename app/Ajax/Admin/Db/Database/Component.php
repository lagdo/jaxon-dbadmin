<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Base\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Base\PageContentTrait;

#[Before('checkDatabaseAccess')]
abstract class Component extends BaseComponent
{
    use PageContentTrait;

    /**
     * @var string
     */
    protected string $overrides = Content::class;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $this->db()->selectDatabase($server, $database, $schema);
    }
}
