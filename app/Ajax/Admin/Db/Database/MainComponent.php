<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Base\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Base\PageContentTrait;

#[Before('checkDatabaseAccess')]
#[After('showBreadcrumbs')]
abstract class MainComponent extends BaseComponent
{
    use PageContentTrait;

    /**
     * @var string
     */
    protected string $overrides = Content::class;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $this->db()->selectDatabase($server, $database, $schema);
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        $pageContent = $this->get('content');
        $counterId = $this->get('counterId');
        return $this->ui()->pageContent($pageContent, $counterId);
    }

    /**
     * @inheritDoc
     */
    protected function footer(): string
    {
        $counterId = $this->get('counterId');
        return $this->ui()->pageFooter($counterId);
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
        $this->set('content', $viewData);
        $this->set('counterId', $checkbox);

        $this->render();
    }
}
