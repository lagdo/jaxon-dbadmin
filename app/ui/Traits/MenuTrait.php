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
        return $this->html->build(
            $this->html->menu(
                $this->html->each($menuActions, fn($title, $id) =>
                    $this->html->menuItem($title)
                        ->setId("dbadmin-menu-action-$id")
                        ->setClass("dbadmin-menu-item menu-action-$id")
                )
            )
        );
    }

    /**
     * @param array $sqlActions
     *
     * @return string
     */
    public function menuCommands(array $sqlActions): string
    {
        return $this->html->build(
            $this->html->buttonGroup(
                $this->html->each($sqlActions, fn($title, $id) =>
                    $this->html->button()->outline()->primary()
                        ->fullWidth()->setClass('dbadmin-menu-item')
                        ->setId("dbadmin-menu-action-$id")->addText($title)
                )
            )
            ->fullWidth(true)
        );
    }

    /**
     * @param array $databases
     *
     * @return string
     */
    public function menuDatabases(array $databases): string
    {
        return $this->html->build(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->option('')->selected(false),
                    $this->html->each($databases, fn($database) =>
                        $this->html->option($database)->selected(false)
                    ))
                    ->setId('dbadmin-dbname-select'),
                $this->html->button()->primary()
                    ->setId('dbadmin-dbname-select-btn')
                    ->setClass('btn-select')->addText('Show')
            )
        );
    }

    /**
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(array $schemas): string
    {
        return $this->html->build(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->each($schemas, fn($schema) =>
                        $this->html->option($schema)->selected(false)
                    ))
                    ->setId('dbadmin-schema-select'),
                $this->html->button()->primary()
                    ->setId('dbadmin-schema-select-btn')
                    ->setClass('btn-select')->addText('Show')
            )
        );
    }
}
