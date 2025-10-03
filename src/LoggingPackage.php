<?php

namespace Lagdo\DbAdmin;

use Jaxon\Plugin\AbstractPackage;
use Lagdo\DbAdmin\Ajax\Log\Commands;
use Lagdo\DbAdmin\Ajax\Log\Wrapper;

use function realpath;
use function Jaxon\cl;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin logging package
 */
class LoggingPackage extends AbstractPackage
{
    /**
     * Get the path to the config file
     *
     * @return string|array
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/logging.php');
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
        return $this->view()->render('dbadmin::codes::css.html') .
            "\n<style>\n" .
            $this->view()->render('dbadmin::codes::styles.css') .
            "\n</style>\n<!-- Spinner CSS code. -->\n<style>\n" .
            $this->view()->render('dbadmin::codes::spin.css') .
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
        return $this->view()->render('dbadmin::codes::script.js') .
            "\n\n// Spinner javascript code.\n" .
            $this->view()->render('dbadmin::codes::spin.js');
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
