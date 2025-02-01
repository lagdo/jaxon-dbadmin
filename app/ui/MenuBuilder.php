<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Database;
use Lagdo\UiBuilder\Jaxon\Builder;

use function Jaxon\pm;
use function Jaxon\rq;

class MenuBuilder
{
    /**
     * @param string $server
     * @param string $user
     *
     * @return string
     */
    public function serverInfo(string $server, string $user): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->col(8)
                ->panel()
                    ->panelBody()->addHtml($server)
                    ->end()
                ->end()
            ->end()
            ->col(4)
                ->panel()
                    ->panelBody()->addHtml($user)
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $actions
     * @param string $activeItem
     *
     * @return string
     */
    public function menuActions(array $actions, string $activeItem): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->menu();
        foreach($actions as $item => $action)
        {
            $htmlBuilder
                ->menuItem($action['title'])
                    ->setClass($item === $activeItem ? 'adminer-menu-item active' : 'adminer-menu-item')
                    ->jxnClick($action['handler'])
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $actions
     * @param string $activeItem
     *
     * @return string
     */
    public function menuCommands(array $actions, string $activeItem): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->buttonGroup(true);
        foreach($actions as $item => $action)
        {
            $htmlBuilder
                ->button()->btnOutline()->btnPrimary()->btnFullWidth()
                    ->setClass($item === $activeItem ? 'adminer-menu-item active' : 'adminer-menu-item')
                    ->addText($action['title'])
                    ->jxnClick($action['handler'])
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $databases
     *
     * @return string
     */
    public function menuDatabases(array $databases): string
    {
        $database = pm()->select('jaxon-dbadmin-database-select');
        $call = rq(Database::class)->select($database)->ifne($database, '');

        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->inputGroup()
                ->formSelect()->setId('jaxon-dbadmin-database-select')
                    ->option(false, '')
                    ->end();
        foreach($databases as $database)
        {
            $htmlBuilder
                    ->option(false, $database)
                    ->end();
        }
        $htmlBuilder
                ->end()
                ->button()->btnPrimary()
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->jxnClick($call)
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param string $database
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(string $database, array $schemas): string
    {
        $schema =  pm()->select('jaxon-dbadmin-schema-select');
        $call = rq(Database::class)->select($database, $schema);

        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->inputGroup()
                ->formSelect()->setId('jaxon-dbadmin-schema-select');
        foreach ($schemas as $schema)
        {
            $htmlBuilder
                    ->option(false, $schema)
                    ->end();
        }
        $htmlBuilder
                ->end()
                ->button()->btnPrimary()
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->jxnClick($call)
                ->end()
            ->end();
        return $htmlBuilder->build();
    }
}
