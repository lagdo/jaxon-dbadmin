<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Ajax\Admin\Admin;
use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;

use function in_array;
use function is_array;
use function realpath;
use function Jaxon\cl;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin package
 */
class DbAdminPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    /**
     * Get the path to the config file
     *
     * @return string|array
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/dbadmin.php');
    }

    /**
     * Get the database servers
     *
     * @return array
     */
    public function getServers(): array
    {
        return $this->getOption('servers', []);
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
    public function getServerAccess(string $server): bool
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
     * @return bool
     */
    public function hasAuditDatabase(): bool
    {
        $options = $this->getOption('audit.database');
        return is_array($options) && isset($options['driver']) &&
            in_array($options['driver'], ['pgsql', 'mysql', 'sqlite']);
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
        return !$defaultServer ||
            !$this->getOption("servers.$defaultServer") ? '' :
                rq(Admin::class)->server($defaultServer);
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
