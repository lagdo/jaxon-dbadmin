<?php

namespace Lagdo\DbAdmin;

use Jaxon\Plugin\Package as JaxonPackage;
use Lagdo\DbAdmin\App\Ajax\Server;
use Lagdo\DbAdmin\Ui\Builder;

use function is_string;
use function realpath;
use function Jaxon\pm;

/**
 * Jaxon DbAdmin package
 */
class Package extends JaxonPackage
{
    /**
     * @var Builder
     */
    protected $uiBuilder;

    /**
     * The constructor
     *
     * @param Builder $uiBuilder
     */
    public function __construct(Builder $uiBuilder)
    {
        $this->uiBuilder = $uiBuilder;
    }

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
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getContainerId(): string
    {
        return 'adminer';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getUserInfoId(): string
    {
        return 'adminer-user-info';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getServerInfoId(): string
    {
        return 'adminer-server-info';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getBreadcrumbsId(): string
    {
        return 'adminer-breadcrumbs';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getMainActionsId(): string
    {
        return 'adminer-main-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getServerActionsId(): string
    {
        return 'adminer-server-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbListId(): string
    {
        return 'adminer-database-list';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getSchemaListId(): string
    {
        return 'adminer-schema-list';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbMenuId(): string
    {
        return 'adminer-database-menu';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbActionsId(): string
    {
        return 'adminer-database-actions';
    }

    /**
     * Get the div id of the HTML element
     *
     * @return string
     */
    public function getDbContentId(): string
    {
        return 'adminer-database-content';
    }

    /**
     * Get all the ids
     *
     * @return array
     */
    public function getIds(): array
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
     * Get the HTML tags to include CSS code and files into the page
     *
     * The code must be enclosed in the appropriate HTML tags.
     *
     * @return string
     */
    public function getCss(): string
    {
        return $this->view()->render('adminer::codes::css', $this->getIds()) .
            "\n" . $this->view()->render('adminer::views::styles', $this->getIds());
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
        return $this->view()->render('adminer::codes::js', $this->getIds());
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
        return $this->view()->render('adminer::codes::script', $this->getIds());
    }

    /**
     * Get the javascript code to execute after page load
     *
     * @return string
     */
    public function getReadyScript(): string
    {
        $servers = $this->getOption('servers', []);
        $default = $this->getOption('default', '');
        if(!is_string($default) || empty($servers[$default]))
        {
            return '';
        }
        return $this->factory()->request(Server::class)->connect($default);
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function getHtml(): string
    {
        // Add an HTML container block for each server in the config file
        $servers = $this->getOption('servers', []);

        $connect = $this->factory()->request(Server::class)
            ->connect(pm()->select('adminer-dbhost-select'));

        $values = $this->getIds();
        $values['connect'] = $connect;
        $values['servers'] = $servers;
        $values['default'] = $this->getOption('default', '');

        return $this->uiBuilder->home($values);
    }
}
