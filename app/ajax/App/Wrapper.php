<?php

namespace Lagdo\DbAdmin\Ajax\App;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Component;

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
