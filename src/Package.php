<?php

namespace Lagdo\DbAdmin;

use Jaxon\Plugin\AbstractPackage;
use Lagdo\DbAdmin\App\Ajax\Admin;

use function realpath;
use function Jaxon\cl;

/**
 * Jaxon DbAdmin package
 */
class Package extends AbstractPackage
{
    /**
     * Get the path to the config file
     *
     * @return string|array
     */
    public static function config()
    {
        return realpath(__DIR__ . '/../config/config.php');
    }

    /**
     * Get the name of a given server
     *
     * @param string $server    The server name in the configuration
     *
     * @return string
     */
    public function getServerName(string $server): string
    {
        return $this->getOption("servers.$server.name", '');
    }

    /**
     * Get a given server options
     *
     * @param string $server    The server name in the configuration
     *
     * @return array
     */
    public function getServerOptions(string $server): array
    {
        return $this->getOption("servers.$server", []);
    }

    /**
     * Get the driver of a given server
     *
     * @param string $server    The server name in the configuration
     *
     * @return string
     */
    public function getServerDriver(string $server): string
    {
        return $this->getOption("servers.$server.driver", '');
    }

    /**
     * Check if the user has access to a server
     *
     * @param string $server      The database server
     *
     * return bool
     */
    public function getServerAccess(string $server)
    {
        // Check in server options
        $serverAccess = $this->getOption("servers.$server.access.server", null);
        if($serverAccess === true || $serverAccess === false)
        {
            return $serverAccess;
        }
        // Check in global options
        return $this->getOption('access.server', true) === true;
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
        return $this->view()->render('adminer::codes::css') .
            "\n" . $this->view()->render('adminer::views::styles');
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
        return $this->view()->render('adminer::codes::js');
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
        return $this->view()->render('adminer::codes::script');
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
}
