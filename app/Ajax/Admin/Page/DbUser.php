<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\MenuComponent;

#[Exclude]
class DbUser extends MenuComponent
{
    use ComponentDataTrait;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->dbUser($this->get('user'));
    }

    /**
     * @param array $dbServer
     *
     * @return void
     */
    public function show(array $dbServer): void
    {
        $this->set('user', $dbServer['user']);
        $this->render();
    }
}
