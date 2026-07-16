<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\AppFunc;
use Lagdo\DbAdmin\App\Ui\UiBuilder;

use function realpath;
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
        return realpath(__DIR__ . '/../config/dbadmin.php');
    }

    /**
     * @return string
     */
    private function editor(): string
    {
        return $this->getConfig()->getOption('ui.query.editor', 'cm');
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $assetsUrl = $this->getConfig()->getOption('assets.url', '/assets');
        $editor = $this->editor();
        $html = $this->view()->render("dbadmin::editor::$editor/css");
        $urls = [
            // Spinner CSS code.
            "$assetsUrl/app/spin.css",
            "$assetsUrl/app/layout.css",
            "$assetsUrl/app/styles.css",
            // DbAdmin tables CSS code.
            "$assetsUrl/app/table.css",
        ];

        return new CssCode(sHtml: $html, aUrls: $urls);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $assetsUrl = $this->getConfig()->getOption('assets.url', '/assets');
        $editor = $this->editor();
        $html = $this->view()->render("dbadmin::editor::$editor/js");
        $urls = [
            // Spinner javascript code.
            "$assetsUrl/app/spin.js",
            "$assetsUrl/app/script.js",
            "$assetsUrl/editor/index.js",
            "$assetsUrl/editor/$editor.js",
        ];

        return new JsCode(sHtml: $html, aUrls: $urls);
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
        return '{' . rq(AppFunc::class)->start() . '}';
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
