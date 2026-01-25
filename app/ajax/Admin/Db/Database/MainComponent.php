<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Base\Component as BaseComponent;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;

#[Before('checkDatabaseAccess')]
#[After('showBreadcrumbs')]
abstract class MainComponent extends BaseComponent
{
    /**
     * @var string
     */
    protected string $overrides = Content::class;

    /**
     * @var array
     */
    private array $pageContent;

    /**
     * @var string
     */
    private string $counterId;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->currentDb();
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
