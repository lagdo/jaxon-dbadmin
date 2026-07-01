<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Lagdo\DbAdmin\App\Ui\UiBuilder;

/**
 * Features for page content components with three parts: header, content and footer.
 */
trait ContentTrait
{
    /**
     * @return UiBuilder
     */
    abstract protected function ui(): UiBuilder;

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
