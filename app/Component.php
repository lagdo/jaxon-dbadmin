<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\Component as JaxonComponent;
use Jaxon\App\Dialog\DialogTrait;
use Lagdo\DbAdmin\App\Ajax\Menu\Sections;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\App\Ui\UiBuilder;

/**
 * @databag dbadmin
 */
abstract class Component extends JaxonComponent
{
    use DialogTrait;
    use CallableTrait;

    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * The facade to database functions
     *
     * @var DbFacade
     */
    private $db;

    /**
     * @var UiBuilder
     */
    private $ui;

    /**
     * @var Translator
     */
    private $trans;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(Package $package, DbFacade $db, UiBuilder $ui, Translator $trans)
    {
        $this->package = $package;
        $this->db = $db;
        $this->ui = $ui;
        $this->trans = $trans;
    }

    /**
     * @return Package
     */
    protected function package(): Package
    {
        return $this->package;
    }

    /**
     * @return DbFacade
     */
    protected function db(): DbFacade
    {
        return $this->db;
    }

    /**
     * @return UiBuilder
     */
    protected function ui(): UiBuilder
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateServerSectionMenu(string $activeItem)
    {
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
    protected function activateServerCommandMenu(string $activeItem)
    {
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
    protected function activateDatabaseSectionMenu(string $activeItem)
    {
        $this->cl(Sections::class)->database($activeItem);
        $this->cl(ServerCommand::class)->server();
        $this->cl(DatabaseCommand::class)->database();
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    protected function activateDatabaseCommandMenu(string $activeItem)
    {
        $this->cl(Sections::class)->database();
        $this->cl(ServerCommand::class)->server();
        $this->cl(DatabaseCommand::class)->database($activeItem);
    }
}
