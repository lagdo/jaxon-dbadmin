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
    use PageContentFixHeightTrait;

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
        return implode("\n", array_filter([
            $this->ui()->panel($this->header(), false),
            $this->ui()->panel($this->content(), true),
            $this->ui()->panel($this->footer(), false),
        ], fn(string $html) => trim($html) !== ''));
    }
}
