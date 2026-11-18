<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent;

#[Databag('dbadmin.audit')]
class AppFunc extends FuncComponent
{
    /**
     * @return void
     */
    public function start(): void
    {
        $this->cl(Commands::class)->page();
    }

    /**
     * @return void
     */
    public function toggleSidebar(): void
    {
        $bag = $this->bag('dbadmin.audit');
        $visible = !$bag->get('sidebar.visible', true);
        $bag->set('sidebar.visible', $visible);

        $this->cl(Sidebar::class)->toggle($visible);
    }
}
