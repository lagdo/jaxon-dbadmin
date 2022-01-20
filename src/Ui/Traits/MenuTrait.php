<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Ui\Builder\AbstractBuilder;

trait MenuTrait
{
    /**
     * @param array $menuActions
     *
     * @return string
     */
    public function menuActions(array $menuActions): string
    {
        $this->htmlBuilder->clear()
            ->menu();
        foreach($menuActions as $id => $title)
        {
            $this->htmlBuilder
                ->menuItem($title)->setClass("adminer-menu-item menu-action-$id")->setId("adminer-menu-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $sqlActions
     *
     * @return string
     */
    public function menuCommands(array $sqlActions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(true);
        foreach($sqlActions as $id => $title)
        {
            $this->htmlBuilder
                ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                    ->setClass('adminer-menu-item')->setId("adminer-menu-action-$id")->addText($title)
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $databases
     *
     * @return string
     */
    public function menuDatabases(array $databases): string
    {
        $this->htmlBuilder->clear()
            ->inputGroup()
                ->formSelect()->setId('adminer-dbname-select')
                    ->option(false, '')
                    ->end();
        foreach($databases as $database)
        {
            $this->htmlBuilder
                    ->option(false, $database)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button(AbstractBuilder::BTN_PRIMARY)->setClass('btn-select')
                    ->setId('adminer-dbname-select-btn')->addText('Show')
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(array $schemas): string
    {
        $this->htmlBuilder->clear()
            ->inputGroup()
                ->formSelect()->setId('adminer-schema-select');
        foreach ($schemas as $schema)
        {
            $this->htmlBuilder
                    ->option(false, $schema)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button(AbstractBuilder::BTN_PRIMARY)->setClass('btn-select')
                    ->setId('adminer-schema-select-btn')->addText('Show')
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }
}
