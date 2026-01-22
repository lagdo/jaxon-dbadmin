<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\Ajax\Admin\Admin;
use Lagdo\DbAdmin\Ajax\Admin\Sidebar;
use Lagdo\DbAdmin\Ajax\Admin\Wrapper;
use Lagdo\UiBuilder\BuilderInterface;

use function realpath;
use function Jaxon\cl;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin package
 */
class DbAdminPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    /**
     * @param BuilderInterface $ui
     */
    public function __construct(private BuilderInterface $ui)
    {}

    /**
     * @inheritDoc
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/dbadmin.php');
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $sCode = "/* Spinner CSS code. */\n" .
            $this->view()->render('dbadmin::codes::spin.css') .
            "\n/* DbAdmin CSS code. */\n" .
            $this->view()->render('dbadmin::codes::styles.css');

        return new CssCode($sCode);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $html = $this->view()->render('dbadmin::codes::js.html');
        $code = "// Spinner javascript code.\n\n" .
            $this->view()->render('dbadmin::codes::spin.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::script.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::editor.js');

        return new JsCode(sCode: $code, sHtml: $html);
    }

    /**
     * Get the javascript code to include into the page
     *
     * The code must NOT be enclosed in HTML tags.
     *
     * @return string
     */
    public function getReadyScript(): string
    {
        $defaultServer = $this->getOption('default');
        return !$defaultServer || !$this->getOption("servers.$defaultServer") ?
            '' : rq(Admin::class)->server($defaultServer);
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function getHtml(): string
    {
        return cl(Admin::class)->html();
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function sidebar(): string
    {
        return $this->ui->build(
            $this->ui->div(
                cl(Sidebar::class)->html()
            )->tbnBind(rq(Sidebar::class))
        );
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->div(
                cl(Wrapper::class)->html()
            )->tbnBind(rq(Wrapper::class))
                ->setId('jaxon-dbadmin')
        );
    }
}
