<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @before checkDatabaseAccess
 * @after showBreadcrumbs
 */
abstract class ContentComponent extends BaseComponent
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
    protected function checkDatabaseAccess()
    {
        [$server, $database, $schema] = $this->bag('dbadmin')->get('db');
        $this->db()->selectDatabase($server, $database, $schema);
        if(!$this->package()->getServerAccess($this->db()->getCurrentServer()))
        {
            throw new DbException('Access to database data is forbidden');
        }
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->mainContent($this->pageContent, $this->counterId);
    }

    /**
     * Display the content of a section
     *
     * @param array $viewData  The data to be displayed in the view
     * @param string $checkbox
     *
     * @return void
     */
    protected function showSection(array $viewData, string $checkbox = '')
    {
        $this->pageContent = $viewData;
        $this->counterId = $checkbox;

        $this->render();
    }
}
