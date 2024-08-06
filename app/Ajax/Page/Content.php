<?php

namespace Lagdo\DbAdmin\App\Ajax\Page;

use Jaxon\App\Component;

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
     * @exclude
     *
     * @param string $html
     *
     * @return void
     */
    public function showHtml(string $html)
    {
        $this->html = $html;
        $this->render();
    }
}
