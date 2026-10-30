<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

trait AppTrait
{
    /**
     * @return AuthInterface
     */
    abstract protected function auth(): AuthInterface;

    /**
     * @return BuilderInterface
     */
    abstract protected function ui(): BuilderInterface;

    /**
     * @return Translator
     */
    abstract protected function trans(): Translator;

    /**
     * @return string
     */
    abstract protected function audit(): string;

    /**
     * @return string
     */
    public function appUser(): string
    {
        $name = $this->auth()->name();
        $userId = $this->auth()->userId();
        $audit = $this->audit();
        $logout = $this->auth()->logout();
        if ($name === '' && $userId === '' && $audit === '' && $logout === '') {
            return '';
        }

        $ui = $this->ui();
        return $ui->build(
            $ui->card(
                $ui->cardBody(
                    $ui->div(
                        $ui->pick(
                            $ui->when($name !== '', fn() =>
                                $ui->span(
                                    $this->trans()->lang('Hello, %s.', $ui->html("<b>$name</b>"))
                                )
                            ),
                            $ui->when($userId !== '', fn() =>
                                $ui->span(
                                    $this->trans()->lang($ui->html("<b>$userId</b>"))
                                )
                            )
                        ),
                        $ui->when($audit !== '', fn() =>
                            $ui->span(
                                $ui->a($this->trans()->lang('Audit'))
                                    ->setHref($audit)
                                    ->setTarget('_blank')
                            )
                        ),
                        $ui->when($logout !== '', fn() =>
                            $ui->span(
                                $ui->a($this->trans()->lang('Logout'))
                                    ->setHref($logout)
                            )
                        )
                    )->setStyle('display: flex; justify-content: space-between;')
                )
            )->setStyle('margin-top: 10px;')
        );
    }

    /**
     * @param array $dbServer
     *
     * @return string
     */
    public function dbServer(array $dbServer): string
    {
        [
            'user' => $user,
            'engine' => $engine,
            'version' => $version,
            'extension' => $extension,
        ] = $dbServer;

        $ui = $this->ui();
        return $ui->build(
            $ui->card(
                $ui->cardBody(
                    $ui->when($user !== '', fn() =>
                        $ui->div(
                            $this->trans()->lang('DB user: %s.',
                                $ui->html("<b>$user</b>"))
                        )
                    ),
                    $ui->div(
                        $this->trans()->lang('%s version: %s.',
                            $engine, $ui->html("<b>$version</b>"))
                    ),
                    $ui->div(
                        $this->trans()->lang('PHP extension %s.',
                            $ui->html("<b>$extension</b>"))
                    )
                )
            )->setStyle('margin-top: 10px;')
        );
    }
}
