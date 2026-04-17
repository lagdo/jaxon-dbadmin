<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\Admin;
use Lagdo\DbAdmin\App\Ui\UiBuilder;

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
        $html = $this->editor() !== 'cm' ? '' :
            $this->view()->render('dbadmin::codes::editor/cm.css.html');
        $code = "/* Spinner CSS code. */\n" .
            $this->view()->render('dbadmin::codes::spin.css') .
            "\n/* DbAdmin CSS code. */\n" .
            $this->view()->render('dbadmin::codes::layout.css') .
            $this->view()->render('dbadmin::codes::styles.css');

        return new CssCode(sCode: $code, sHtml: $html);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $editorJsName = $this->editor() === 'cm' ? 'cm.js' : 'ace.js';
        $html = $this->view()->render("dbadmin::codes::editor/$editorJsName.html");
        $code = "// Spinner javascript code.\n\n" .
            $this->view()->render('dbadmin::codes::spin.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::script.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::editor/index.js') . "\n\n" .
            $this->view()->render("dbadmin::codes::editor/$editorJsName");

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
        return '{' . rq(Admin::class)->start() . '}';
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
