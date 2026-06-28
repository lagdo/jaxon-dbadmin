<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\MenuComponent;

#[Exclude]
class DbServer extends MenuComponent
{
    use ComponentDataTrait;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->server($this->get('server'));
    }

    /**
     * @param string $server
     *
     * @return void
     */
    public function show(string $server): void
    {
        $this->set('server', $server);
        $this->render();
    }
}
