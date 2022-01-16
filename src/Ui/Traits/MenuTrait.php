<?php

namespace Lagdo\DbAdmin\Ui\Traits;

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
                ->menuItem($title, "adminer-menu-item menu-action-$id")->setId("adminer-menu-action-$id")
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
                ->button('default', 'adminer-menu-item', false, true)->setId("adminer-menu-action-$id")->addText($title)
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
                ->select()->setId('adminer-dbname-select')
                    ->option('')->setValue('')
                    ->end();
        foreach($databases as $database)
        {
            $this->htmlBuilder
                    ->option($database)->setValue($database)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button('primary', 'btn-select')->setId('adminer-dbname-select-btn')->addText('Show')
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
                ->select()->setId('adminer-schema-select');
        foreach ($schemas as $schema)
        {
            $this->htmlBuilder
                    ->option($schema)->setValue($schema)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button('primary', 'btn-select')->setId('adminer-schema-select-btn')->addText('Show')
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }
}
