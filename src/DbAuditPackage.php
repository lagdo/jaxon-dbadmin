<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\Ajax\Audit\Commands;
use Lagdo\DbAdmin\Ui\UiBuilder;

use function realpath;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin audit package
 */
class DbAuditPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    /**
     * @param UiBuilder $ui
     */
    public function __construct(private UiBuilder $ui)
    {}

    /**
     * Get the path to the config file
     *
     * @return string|array
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/dbaudit.php');
    }

    /**
     * Get a given server options
     *
     * @return array
     */
    public function getServerOptions(): array
    {
        return $this->getOption('database', []);
    }

    /**
     * Get the driver of a given server
     *
     * @return string
     */
    public function getServerDriver(): string
    {
        return $this->getOption('database.driver', '');
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $code = "/* Spinner CSS code. */\n" .
            $this->view()->render('dbadmin::codes::spin.css') .
            "\n/* DbAdmin CSS code. */\n" .
            $this->view()->render('dbadmin::codes::layout.css') .
            $this->view()->render('dbadmin::codes::styles.css');

        return new CssCode($code);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $html = $this->view()->render('dbadmin::codes::js.html');
        $code = "// Spinner javascript code.\n\n" .
            $this->view()->render('dbadmin::codes::spin.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::script.js');

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
        return rq(Commands::class)->page();
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function layout(): string
    {
        return $this->ui->audit();
    }
}
