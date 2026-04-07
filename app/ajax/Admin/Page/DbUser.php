<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\MenuComponent;

class DbUser extends MenuComponent
{
    use ComponentDataTrait;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->user($this->get('user'));
    }

    /**
     * @param string $user
     *
     * @return void
     */
    #[Exclude]
    public function show(string $user): void
    {
        $this->set('user', $user);
        $this->render();
    }
}
