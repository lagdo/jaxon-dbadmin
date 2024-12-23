<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Db\Database;
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
     *
     * @return string
     */
    public function menuActions(array $actions): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->menu();
        foreach($actions as $action)
        {
            $htmlBuilder
                ->menuItem($action['title'])
                    ->setClass("adminer-menu-item")
                    ->jxnClick($action['handler'])
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $actions
     *
     * @return string
     */
    public function menuCommands(array $actions): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->buttonGroup(true);
        foreach($actions as $action)
        {
            $htmlBuilder
                ->button()->btnOutline()->btnFullWidth()
                    ->setClass('adminer-menu-item')
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
        $database = pm()->select('jaxon-dbadmin-database-select');
        $call = rq(Database::class)->select($database)->when($database);
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
        $schema =  pm()->select('jaxon-dbadmin-schema-select');
        $call = rq(Database::class)->select($database, $schema);
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
