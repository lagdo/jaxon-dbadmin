<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Database;
use Lagdo\DbAdmin\App\Ui\TabApp;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\select;
use function Jaxon\rq;

class MenuBuilder
{
    /**
     * @param BuilderInterface $ui
     */
    public function __construct(protected BuilderInterface $ui)
    {}

    /**
     * @param string $user
     *
     * @return string
     */
    public function user(string $user): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->panel(
                    $this->ui->panelBody($this->ui->html($user))
                ),
            )->width(12)
        );
    }

    /**
     * @param string $server
     *
     * @return string
     */
    public function server(string $server): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->panel(
                    $this->ui->panelBody($this->ui->html($server))
                )
            )->width(12)
        );
    }

    /**
     * @param array $actions
     * @param string $activeItem
     *
     * @return string
     */
    public function actions(array $actions, string $activeItem): string
    {
        return $this->ui->build(
            $this->ui->menu(
                $this->ui->each($actions, fn($action, $item) =>
                    $this->ui->menuItem($action['title'])
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
    public function commands(array $actions, string $activeItem): string
    {
        return $this->ui->build(
            $this->ui->buttonGroup(
                $this->ui->each($actions, fn($action, $item) =>
                    $this->ui->button($this->ui->text($action['title']))
                        ->outline()
                        ->primary()
                        ->fullWidth()
                        ->setClass($item === $activeItem ? 'dbadmin-menu-item active' : 'dbadmin-menu-item')
                        ->jxnClick($action['handler'])
                ),
            )
            ->fullWidth(),
        );
    }

    /**
     * @param array $databases
     * @param string|null $selected
     *
     * @return string
     */
    public function databases(array $databases, string|null $selected = null): string
    {
        $dbSelectId = TabApp::id('jaxon-dbadmin-database-select');
        $database = select($dbSelectId);
        $call = rq(Database::class)->select($database)->ifne($database, '');

        return $this->ui->build(
            $this->ui->form(
                $this->ui->inputGroup(
                    $this->ui->select(
                        $this->ui->when(!$selected, fn() =>
                            $this->ui->option($this->ui->text(''))
                                ->selected(false)
                        ),
                        $this->ui->each($databases, fn($database) =>
                            $this->ui->option($this->ui->text($database))
                                ->selected($selected === $database)
                        )
                    )->setId($dbSelectId),
                    $this->ui->button($this->ui->text('Show'))
                        ->primary()
                        ->setClass('btn-select')
                        ->jxnClick($call)
                )
            )
        );
    }

    /**
     * @param string $database
     * @param array $schemas
     *
     * @return string
     */
    public function schemas(string $database, array $schemas): string
    {
        $schemaSelectId = TabApp::id('jaxon-dbadmin-schema-select');
        $schema = select($schemaSelectId);
        $call = rq(Database::class)->select($database, $schema);

        return $this->ui->build(
            $this->ui->form(
                $this->ui->inputGroup(
                    $this->ui->select(
                        $this->ui->option($this->ui->text(''))
                            ->selected(false),
                        $this->ui->each($schemas, fn($schema) =>
                            $this->ui->option($this->ui->text($schema))
                                ->selected(false)
                        )
                    )->setId($schemaSelectId),
                    $this->ui->button($this->ui->text('Show'))
                        ->primary()
                        ->setClass('btn-select')
                        ->jxnClick($call)
                )
            )
        );
    }
}
