<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;

#[Exclude]
class Content extends Component
{
    /**
     * @var string
     */
    private $html;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return void
     */
    public function showHtml(string $html): void
    {
        $this->html = $html;
        $this->render();
    }
}
