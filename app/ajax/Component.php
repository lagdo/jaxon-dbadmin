<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\Component as JaxonComponent;
use Jaxon\App\Dialog\DialogTrait;
use Lagdo\DbAdmin\Ajax\App\Menu\Sections;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

/**
 * @databag dbadmin
 */
abstract class Component extends JaxonComponent
{
    use DialogTrait;
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected Package $package, protected DbFacade $db,
        protected UiBuilder $ui, protected Translator $trans)
    {}

    /**
     * @param string|null $server
     *
     * @return bool
     */
    protected function hasServerAccess(string|null $server = null): bool
    {
        if ($server === null) {
            $server = $this->bag('dbadmin')->get('db')[0] ?? '';
        }
        return $this->package()->getServerAccess($server);
    }

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
        [, $database] = $this->bag('dbadmin')->get('db');
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
        [, $database] = $this->bag('dbadmin')->get('db');
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
