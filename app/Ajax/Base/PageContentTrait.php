<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Lagdo\DbAdmin\App\Ui\UiBuilder;

use function array_filter;
use function implode;
use function trim;

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
     * @return string|array
     */
    protected function header(): string|array
    {
        return '';
    }

    /**
     * @return string|array
     */
    abstract protected function content(): string|array;

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
        return implode("\n", array_filter([
            $this->ui()->panel($this->header(), 'margin-bottom: 10px;'),
            $this->ui()->panel($this->content()),
            $this->ui()->panel($this->footer(), 'margin-top: 10px;'),
        ], fn(string $html) => trim($html) !== ''));
    }
}
