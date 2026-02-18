<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;

#[Exclude]
class Content extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->get('html');
    }
}
