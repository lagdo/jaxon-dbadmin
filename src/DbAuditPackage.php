<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\Plugin\AbstractPackage;
use Lagdo\DbAdmin\Ajax\Audit\Commands;
use Lagdo\DbAdmin\Ajax\Audit\Wrapper;

use function realpath;
use function Jaxon\cl;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin audit package
 */
class DbAuditPackage extends AbstractPackage
{
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
     * Get the HTML tags to include CSS code and files into the page
     *
     * The code must be enclosed in the appropriate HTML tags.
     *
     * @return string
     */
    public function getCss(): string
    {
        return "<style>\n" .
            $this->view()->render('dbadmin::codes::spin.css') .
            "\n/* Spinner CSS code. */\n" .
            $this->view()->render('dbadmin::codes::styles.css') .
            "\n</style>\n";
    }

    /**
     * Get the HTML tags to include javascript code and files into the page
     *
     * The code must be enclosed in the appropriate HTML tags.
     *
     * @return string
     */
    public function getJs(): string
    {
        return $this->view()->render('dbadmin::codes::js.html');
    }

    /**
     * Get the javascript code to include into the page
     *
     * The code must NOT be enclosed in HTML tags.
     *
     * @return string
     */
    public function getScript(): string
    {
        return "// Spinner javascript code.\n\n" .
            $this->view()->render('dbadmin::codes::spin.js') . "\n\n" .
            $this->view()->render('dbadmin::codes::script.js');
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
    public function getHtml(): string
    {
        return cl(Wrapper::class)->html();
    }
}
