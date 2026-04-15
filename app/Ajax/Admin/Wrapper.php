<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;

#[Exclude]
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
