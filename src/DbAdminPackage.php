<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Lagdo\DbAdmin\Ajax\Admin\Admin;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\DbAdmin\Ui\TabEditor;
use Lagdo\DbAdmin\Ui\UiBuilder;

use function realpath;
use function Jaxon\jaxon;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin package
 */
class DbAdminPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    /**
     * @param UiBuilder $ui
     */
    public function __construct(private UiBuilder $ui)
    {}

    /**
     * @inheritDoc
     */
    public static function config(): string
    {
        jaxon()->callback()->boot(function() {
            $databag = jaxon()->di()->g(DatabagPlugin::class);
            TabApp::$databag = $databag;
            TabEditor::$databag = $databag;
        });
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
            $this->view()->render('dbadmin::codes::layout.css') .
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

        // Toast library for the SQL editor.
        $config = $this->getConfig();
        if ($config->hasOption('toast.lib')) {
            $lib = $config->getOption('toast.lib');
            $code .= "\n\njaxon.dom.ready(() => jaxon.dbadmin.setToastLib('$lib'));";
        }

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
    public function layout(): string
    {
        return $this->ui->admin();
    }
}
