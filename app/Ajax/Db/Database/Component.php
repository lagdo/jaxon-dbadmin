<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

/**
 * @exclude
 */
abstract class Component extends BaseComponent
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

    /**
     * @var array
     */
    private $content;

    /**
     * @var string
     */
    private $counterId;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->renderMainContent($this->content), $this->counterId);
    }

    /**
     * Display the content of a section
     *
     * @param array  $viewData  The data to be displayed in the view
     * @param array  $contentData  The data to be displayed in the view
     * @param array  $actions  The menu actions
     *
     * @return void
     */
    protected function showSection(array $viewData, array $contentData, array $actions)
    {
        // Make data available to views
        $this->view()->shareValues($viewData);

        // Set main menu buttons
        $this->cl(PageActions::class)->update($actions);

        $this->content = $contentData;
        $this->counterId = $contentData['checkbox'] ?? '';

        $this->refresh();
    }
}
