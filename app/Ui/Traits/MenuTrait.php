<?php

namespace Lagdo\DbAdmin\App\Ui\Traits;

use Lagdo\UiBuilder\Jaxon\Builder;

trait MenuTrait
{
    /**
     * @param array $menuActions
     *
     * @return string
     */
    public function menuActions(array $menuActions): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->menu();
        foreach($menuActions as $id => $title)
        {
            $htmlBuilder
                ->menuItem($title)->setClass("adminer-menu-item menu-action-$id")->setId("adminer-menu-action-$id")
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $sqlActions
     *
     * @return string
     */
    public function menuCommands(array $sqlActions): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->buttonGroup(true);
        foreach($sqlActions as $id => $title)
        {
            $htmlBuilder
                ->button()->btnOutline()->btnPrimary()->btnFullWidth()
                    ->setClass('adminer-menu-item')->setId("adminer-menu-action-$id")->addText($title)
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
                ->formSelect()->setId('adminer-dbname-select')
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
                ->button()->btnPrimary()->setClass('btn-select')
                    ->setId('adminer-dbname-select-btn')->addText('Show')
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(array $schemas): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->inputGroup()
                ->formSelect()->setId('adminer-schema-select');
        foreach ($schemas as $schema)
        {
            $htmlBuilder
                    ->option(false, $schema)
                    ->end();
        }
        $htmlBuilder
                ->end()
                ->button()->btnPrimary()->setClass('btn-select')
                    ->setId('adminer-schema-select-btn')->addText('Show')
                ->end()
            ->end();
        return $htmlBuilder->build();
    }
}
