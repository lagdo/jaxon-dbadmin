<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Page\Content;

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
    private $pageContent;

    /**
     * @var string
     */
    private $counterId;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->pageContent, $this->counterId);
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
        $this->pageContent['checkbox'] = $checkbox;
        $this->counterId = $checkbox;

        $this->render();
    }
}
