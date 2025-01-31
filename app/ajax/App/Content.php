<?php

namespace Lagdo\DbAdmin\Ajax\App;

use Lagdo\DbAdmin\Ajax\Component;

/**
 * @exclude
 */
class Content extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->content();
    }
}
