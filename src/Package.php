<?php

namespace Lagdo\DbAdmin;

use Jaxon\Plugin\Package as JaxonPackage;
use Lagdo\DbAdmin\App\Ajax\Server;
use Lagdo\DbAdmin\Ui\Builder;

use function array_key_exists;
use function array_walk;
use function jaxon;
use function pm;

/**
 * Adminer package
 */
class Package extends JaxonPackage
{
    /**
     * @var Builder
     */
    protected $uiBuilder;

    /**
     * The constructor
     */
    public function __construct()
    {
        jaxon()->callback()->boot(function() {
            $template = $this->getConfig()->getOption('template', 'bootstrap3');
            jaxon()->di()->val('dbadmin_config_builder', $template);
            $this->uiBuilder = jaxon()->di()->get(Builder::class);
            jaxon()->template()->pagination(__DIR__ . "/../templates/views/$template/pagination/");
        });
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getContainerId()
    {
        return 'adminer';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getUserInfoId()
    {
        return 'adminer-user-info';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getServerInfoId()
    {
        return 'adminer-server-info';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getBreadcrumbsId()
    {
        return 'adminer-breadcrumbs';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getMainActionsId()
    {
        return 'adminer-main-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getServerActionsId()
    {
        return 'adminer-server-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbListId()
    {
        return 'adminer-database-list';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getSchemaListId()
    {
        return 'adminer-schema-list';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbMenuId()
    {
        return 'adminer-database-menu';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbActionsId()
    {
        return 'adminer-database-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbContentId()
    {
        return 'adminer-database-content';
    }

    /**
     * Get all the ids
     *
     * @return array
     */
    public function getIds()
    {
        return [
            'containerId' => $this->getContainerId(),
            'userInfoId' => $this->getUserInfoId(),
            'serverInfoId' => $this->getServerInfoId(),
            'breadcrumbsId' => $this->getBreadcrumbsId(),
            'mainActionsId' => $this->getMainActionsId(),
            'serverActionsId' => $this->getServerActionsId(),
            'dbListId' => $this->getDbListId(),
            'schemaListId' => $this->getSchemaListId(),
            'dbMenuId' => $this->getDbMenuId(),
            'dbActionsId' => $this->getDbActionsId(),
            'dbContentId' => $this->getDbContentId(),
        ];
    }

    /**
     * Get the path to the config file
     *
     * @return string
     */
    public static function getConfigFile()
    {
        return realpath(__DIR__ . '/../config/config.php');
    }

    /**
     * Get a given server options
     *
     * @param string $server    The server name in the configuration
     *
     * @return array
     */
    public function getServerOptions(string $server)
    {
        return $this->getConfig()->getOption("servers.$server", []);
    }

    /**
     * Get the driver of a given server
     *
     * @param string $server    The server name in the configuration
     *
     * @return string
     */
    public function getServerDriver(string $server)
    {
        return $this->getConfig()->getOption("servers.$server.driver", '');
    }

    /**
     * Get the default server to connect to
     *
     * @return string
     */
    private function getDefaultServer()
    {
        $servers = $this->getConfig()->getOption('servers', []);
        $default = $this->getConfig()->getOption('default', '');
        if(array_key_exists($default, $servers))
        {
            return $default;
        }
        // if(count($servers) > 0)
        // {
        //     return $servers[0];
        // }
        return '';
    }

    /**
     * Get the HTML tags to include CSS code and files into the page
     *
     * The code must be enclosed in the appropriate HTML tags.
     *
     * @return string
     */
    public function getCss()
    {
        return $this->view()->render('adminer::codes::css', $this->getIds()) .
            "\n" . $this->view()->render('adminer::templates::styles', $this->getIds());
    }

    /**
     * Get the HTML tags to include javascript code and files into the page
     *
     * The code must be enclosed in the appropriate HTML tags.
     *
     * @return string
     */
    public function getJs()
    {
        return $this->view()->render('adminer::codes::js', $this->getIds());
    }

    /**
     * Get the javascript code to include into the page
     *
     * The code must NOT be enclosed in HTML tags.
     *
     * @return string
     */
    public function getScript()
    {
        return $this->view()->render('adminer::codes::script', $this->getIds());
    }

    /**
     * Get the javascript code to execute after page load
     *
     * @return string
     */
    public function getReadyScript()
    {
        if(!($server = $this->getDefaultServer()))
        {
            return '';
        }
        return jaxon()->request(Server::class)->connect($server);
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function getHtml()
    {
        // Add an HTML container block for each server in the config file
        $servers = $this->getConfig()->getOption('servers', []);
        array_walk($servers, function(&$server) {
            $server = $server['name'];
        });

        $connect = jaxon()->request(Server::class)->connect(pm()->select('adminer-dbhost-select'));

        $values = $this->getIds();
        $values['connect'] = $connect;
        $values['servers'] = $servers;
        $values['default'] = $this->getConfig()->getOption('default', '');

        return $this->uiBuilder->home($values);
    }
}
