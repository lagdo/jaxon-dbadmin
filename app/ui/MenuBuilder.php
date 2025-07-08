<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Database;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\pm;
use function Jaxon\rq;

class MenuBuilder
{
    /**
     * @param BuilderInterface $html
     */
    public function __construct(protected BuilderInterface $html)
    {}

    /**
     * @param string $server
     * @param string $user
     *
     * @return string
     */
    public function serverInfo(string $server, string $user): string
    {
        return $this->html->build(
            $this->html->col(
                $this->html->panel(
                    $this->html->panelBody()->addHtml($server)
                )
            )->width(8),
            $this->html->col(
                $this->html->panel(
                    $this->html->panelBody()->addHtml($user)
                ),
            )->width(4),
        );
    }

    /**
     * @param array $actions
     * @param string $activeItem
     *
     * @return string
     */
    public function menuActions(array $actions, string $activeItem): string
    {
        return $this->html->build(
            $this->html->menu(
                $this->html->each($actions, fn($action, $item) =>
                    $this->html->menuItem($action['title'])
                        ->setClass($item === $activeItem ? 'dbadmin-menu-item active' : 'dbadmin-menu-item')
                        ->jxnClick($action['handler'])
                )
            )
        );
    }

    /**
     * @param array $actions
     * @param string $activeItem
     *
     * @return string
     */
    public function menuCommands(array $actions, string $activeItem): string
    {
        return $this->html->build(
            $this->html->buttonGroup(
                $this->html->each($actions, fn($action, $item) =>
                    $this->html->button()
                        ->outline()
                        ->primary()
                        ->fullWidth()
                        ->setClass($item === $activeItem ? 'dbadmin-menu-item active' : 'dbadmin-menu-item')
                        ->addText($action['title'])
                        ->jxnClick($action['handler'])
                ),
            )
            ->fullWidth(),
        );
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

        return $this->html->build(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->option()
                        ->selected(false)->addText(''),
                    $this->html->each($databases, fn($database) =>
                        $this->html->option()
                            ->selected(false)->addText($database)
                    )
                )
                ->setId('jaxon-dbadmin-database-select'),
                $this->html->button()
                    ->primary()
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->jxnClick($call)
            ),
        );
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

        return $this->html->build(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->option()
                        ->selected(false)->addText(''),
                    $this->html->each($schemas, fn($schema) =>
                        $this->html->option()
                            ->selected(false)->addText($schema)
                    )
                )
                ->setId('jaxon-dbadmin-schema-select'),
                $this->html->button()
                    ->primary()
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->jxnClick($call)
            )
        );
    }
}
