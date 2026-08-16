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

        return $this->ui()->build(
            $this->ui()->card(
                $this->ui()->cardBody(
                    $this->ui()->div(
                        $this->ui()->pick(
                            $this->ui()->when($name !== '', fn() =>
                                $this->ui()->span(
                                    $this->trans()->lang('Hello, %s.', $this->ui()->html("<b>$name</b>"))
                                )
                            ),
                            $this->ui()->when($userId !== '', fn() =>
                                $this->ui()->span(
                                    $this->trans()->lang($this->ui()->html("<b>$userId</b>"))
                                )
                            )
                        ),
                        $this->ui()->when($audit !== '', fn() =>
                            $this->ui()->span(
                                $this->ui()->a($this->trans()->lang('Audit'))
                                    ->setHref($audit)
                                    ->setTarget('_blank')
                            )
                        ),
                        $this->ui()->when($logout !== '', fn() =>
                            $this->ui()->span(
                                $this->ui()->a($this->trans()->lang('Logout'))
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

        return $this->ui()->build(
            $this->ui()->card(
                $this->ui()->cardBody(
                    $this->ui()->when($user !== '', fn() =>
                        $this->ui()->div(
                            $this->trans()->lang('DB user: %s.',
                                $this->ui()->html("<b>$user</b>"))
                        )
                    ),
                    $this->ui()->div(
                        $this->trans()->lang('%s version: %s.',
                            $engine, $this->ui()->html("<b>$version</b>"))
                    ),
                    $this->ui()->div(
                        $this->trans()->lang('PHP extension %s.',
                            $this->ui()->html("<b>$extension</b>"))
                    )
                )
            )->setStyle('margin-top: 10px;')
        );
    }
}
