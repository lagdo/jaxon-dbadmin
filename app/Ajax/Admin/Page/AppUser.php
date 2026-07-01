<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\MenuComponent;

#[Exclude]
class AppUser extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->appUser();
    }
}
