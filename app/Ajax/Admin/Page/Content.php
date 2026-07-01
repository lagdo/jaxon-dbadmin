<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;

/**
 * The default content component, which will be overriden by components that display contents.
 */
#[Exclude]
class Content extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return '';
    }
}
