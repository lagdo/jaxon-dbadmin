<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;

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
    #[Exclude]
    public function showHtml(string $html): void
    {
        $this->html = $html;
        $this->render();
    }
}
