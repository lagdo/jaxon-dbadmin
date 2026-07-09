<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ajax\Base\Component as BaseComponent;

#[Before('checkDatabaseAccess')]
#[After('showBreadcrumbs')]
abstract class MainComponent extends BaseComponent
{
    use ContentTrait;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $this->driver()->selectDatabase($server, $database, $schema);
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
