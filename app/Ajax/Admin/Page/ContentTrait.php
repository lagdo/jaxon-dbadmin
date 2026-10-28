<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Lagdo\DbAdmin\App\Ui\AdminUiBuilder;

/**
 * Features for page content components with three parts: header, content and footer.
 */
trait ContentTrait
{
    /**
     * @return AdminUiBuilder
     */
    abstract protected function ui(): AdminUiBuilder;

    /**
     * @return string|array
     */
    abstract protected function content(): string|array;

    /**
     * @inheritDoc
     */
    protected function overrides(): string
    {
        return Content::class;
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->panel($this->content());
    }
}
