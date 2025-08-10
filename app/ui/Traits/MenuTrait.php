<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;

trait MenuTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param array $menuActions
     *
     * @return string
     */
    public function menuActions(array $menuActions): string
    {
        $html = $this->builder();
        return $html->build(
            $html->menu(
                $html->each($menuActions, fn($title, $id) =>
                    $html->menuItem($title)
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
        $html = $this->builder();
        return $html->build(
            $html->buttonGroup(
                $html->each($sqlActions, fn($title, $id) =>
                    $html->button($this->html->text($title))->outline()->primary()
                        ->fullWidth()->setClass('dbadmin-menu-item')
                        ->setId("dbadmin-menu-action-$id")
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
        $html = $this->builder();
        return $html->build(
            $html->inputGroup(
                $html->formSelect(
                    $html->option('')->selected(false),
                    $html->each($databases, fn($database) =>
                        $html->option($database)->selected(false)
                    ))
                    ->setId('dbadmin-dbname-select'),
                $html->button($this->html->text('Show'))->primary()
                    ->setId('dbadmin-dbname-select-btn')
                    ->setClass('btn-select')
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
        $html = $this->builder();
        return $html->build(
            $html->inputGroup(
                $html->formSelect(
                    $html->each($schemas, fn($schema) =>
                        $html->option($schema)->selected(false)
                    ))
                    ->setId('dbadmin-schema-select'),
                $html->button($this->html->text('Show'))->primary()
                    ->setId('dbadmin-schema-select-btn')
                    ->setClass('btn-select')
            )
        );
    }
}
