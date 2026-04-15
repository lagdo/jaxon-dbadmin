<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Lagdo\DbAdmin\App\Ui\UiBuilder;

/**
 * Features for page content components with three parts: header, content and footer.
 */
trait PageContentTrait
{
    /**
     * @return UiBuilder
     */
    abstract protected function ui(): UiBuilder;

    /**
     * @return string
     */
    protected function header(): string
    {
        return '';
    }

    /**
     * @return string
     */
    abstract protected function content(): string;

    /**
     * @return string
     */
    protected function footer(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return implode("\n", array_map($this->ui()->panel(...), array_filter([
            $this->header(),
            $this->content(),
            $this->footer(),
        ], fn(string $html) => trim($html) !== '')));
    }
}
