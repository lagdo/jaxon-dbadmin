<?php

namespace Lagdo\DbAdmin\App\Ui;

use Jaxon\App\Ajax\Jaxon;
use Lagdo\DbAdmin\App\Ajax\Admin\DbFunc;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function in_array;
use function Jaxon\pm;
use function Jaxon\rq;

class MenuBuilder
{
    /**
     * @param Translator $trans
     * @param Tab $tab
     * @param BuilderInterface $ui
     * @param AuthInterface $auth
     * @param Jaxon $jaxon
     */
    public function __construct(private Translator $trans, private Tab $tab,
        private BuilderInterface $ui, private AuthInterface $auth, private Jaxon $jaxon)
    {}

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @return string
     */
    public function appUser(): string
    {
        $name = $this->auth->name();
        $user = $this->auth->user();
        $auditUsers = $this->jaxon->getAppOption('audit.users', []);
        $audit = in_array($user, $auditUsers) ? $this->auth->audit() : '';
        $logout = $this->auth->logout();
        if ($name === '' && $user === '' && $audit === '' && $logout === '') {
            return '';
        }

        return $this->ui->build(
            $this->ui->card(
                $this->ui->cardBody(
                    $this->ui->div(
                        $this->ui->pick(
                            $this->ui->when($name !== '', fn() =>
                                $this->ui->span(
                                    $this->trans->lang('Hello, %s.', $this->ui->html("<b>$name</b>"))
                                )
                            ),
                            $this->ui->when($user !== '', fn() =>
                                $this->ui->span(
                                    $this->trans->lang($this->ui->html("<b>$user</b>"))
                                )
                            )
                        ),
                        $this->ui->when($audit !== '', fn() =>
                            $this->ui->span(
                                $this->ui->a($this->trans->lang('Audit'))
                                    ->setHref($audit)
                                    ->setTarget('_blank')
                            )
                        ),
                        $this->ui->when($logout !== '', fn() =>
                            $this->ui->span(
                                $this->ui->a($this->trans->lang('Logout'))
                                    ->setHref($logout)
                            )
                        )
                    )->setStyle('display: flex; justify-content: space-between;')
                )
            )->setStyle('margin-top: 10px;')
        );
    }

    /**
     * @param string $user
     *
     * @return string
     */
    public function dbUser(string $user): string
    {
        return $user === '' ? '' : $this->ui->build(
            $this->ui->card(
                $this->ui->cardBody(
                    $this->trans->lang('Logged as: %s.', $this->ui->html("<b>$user</b>"))
                )
            )->setStyle('margin-top: 10px;')
        );
    }

    /**
     * @param string $engine
     * @param string $version
     * @param string $extension
     *
     * @return string
     */
    public function dbServer(string $engine, string $version, string $extension): string
    {
        return $this->ui->build(
            $this->ui->card(
                $this->ui->cardBody(
                    $this->ui->div(
                        $this->trans->lang('%s version: %s.',
                            $engine, $this->ui->html("<b>$version</b>"))
                    ),
                    $this->ui->div(
                        $this->trans->lang('PHP extension %s.',
                            $this->ui->html("<b>$extension</b>"))
                    )
                )
            )->setStyle('margin-top: 10px;')
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
