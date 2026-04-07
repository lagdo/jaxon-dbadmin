<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\MenuComponent;

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
    #[Exclude]
    public function show(string $server): void
    {
        $this->set('server', $server);
        $this->render();
    }
}
