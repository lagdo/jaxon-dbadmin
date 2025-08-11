<?php

namespace Lagdo\DbAdmin\Ajax\App;

use Lagdo\DbAdmin\Ajax\Component;

/**
 * @exclude
 */
class Wrapper extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->wrapper();
    }
}
