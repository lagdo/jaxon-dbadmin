<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\Component as BaseComponent;
use Lagdo\DbAdmin\Ajax\App\Page\Content;

/**
 * @before checkDatabaseAccess
 * @after showBreadcrumbs
 */
abstract class MainComponent extends BaseComponent
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

    /**
     * @var array
     */
    private $pageContent;

    /**
     * @var string
     */
    private $counterId;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->bag('dbadmin')->get('db');
        $this->db()->selectDatabase($server, $database, $schema);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->pageContent($this->pageContent, $this->counterId);
    }

    /**
     * Display the content of a section
     *
     * @param array $viewData  The data to be displayed in the view
     * @param string $checkbox
     *
     * @return void
     */
    protected function showSection(array $viewData, string $checkbox = ''): void
    {
        $this->pageContent = $viewData;
        $this->counterId = $checkbox;

        $this->render();
    }
}
