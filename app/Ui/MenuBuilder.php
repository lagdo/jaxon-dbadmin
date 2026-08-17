<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin\DbFunc;
use Lagdo\DbAdmin\App\DbAdminPackage;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\pm;
use function Jaxon\rq;

class MenuBuilder
{
    use AppTrait;

    /**
     * @param Translator $trans
     * @param Tab $tab
     * @param BuilderInterface $ui
     * @param AuthInterface $auth
     * @param DbAdminPackage $package
     */
    public function __construct(private Translator $trans, private Tab $tab,
        private BuilderInterface $ui, private AuthInterface $auth,
        private DbAdminPackage $package)
    {}

    /**
     * @return AuthInterface
     */
    protected function auth(): AuthInterface
    {
        return $this->auth;
    }

    /**
     * @return BuilderInterface
     */
    protected function ui(): BuilderInterface
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
     * @return string
     */
    protected function audit(): string
    {
        return $this->package->checkAuditAccess($this->auth->userId()) ?
            $this->auth->audit() : '';
    }

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
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
            )->fullWidth(),
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
        $dbSelectId = $this->tab()->app()->id('jaxon-dbadmin-database-select');
        $database = pm()->select($dbSelectId);
        $call = rq(DbFunc::class)->database($database)->ifne($database, '');

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
        $schemaSelectId = $this->tab()->app()->id('jaxon-dbadmin-schema-select');
        $schema = pm()->select($schemaSelectId);
        $call = rq(DbFunc::class)->database($database, $schema);

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
