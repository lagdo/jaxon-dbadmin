<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\Component as JaxonComponent;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Sections;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Command as ServerCommand;

#[Databag('dbadmin')]
abstract class Component extends JaxonComponent
{
    use ComponentTrait;
    use TabItemTrait;

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateServerSectionMenu(string $activeItem): void
    {
        if (!$this->hasServerAccess()) {
            return;
        }

        $this->cl(Sections::class)->server($activeItem);
        $this->cl(ServerCommand::class)->server();
        // Reset the database command menu only if there is an active database
        [, $database] = $this->getCurrentDb();
        if($database !== '')
        {
            $this->cl(DatabaseCommand::class)->database();
        }
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateServerCommandMenu(string $activeItem): void
    {
        if (!$this->hasServerAccess()) {
            return;
        }

        $this->cl(Sections::class)->server();
        $this->cl(ServerCommand::class)->server($activeItem);
        // Reset the database command menu only if there is an active database
        [, $database] = $this->getCurrentDb();
        if($database !== '')
        {
            $this->cl(DatabaseCommand::class)->database();
        }
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateDatabaseSectionMenu(string $activeItem): void
    {
        $this->cl(Sections::class)->database($activeItem);
        $this->hasServerAccess() && $this->cl(ServerCommand::class)->server();
        $this->cl(DatabaseCommand::class)->database();
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateDatabaseCommandMenu(string $activeItem): void
    {
        $this->cl(Sections::class)->database();
        $this->hasServerAccess() && $this->cl(ServerCommand::class)->server();
        $this->cl(DatabaseCommand::class)->database($activeItem);
    }
}
