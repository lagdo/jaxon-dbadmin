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
        $dbServer = $this->get('dbServer');
        return $this->ui()->dbServer($dbServer['engine'],
            $dbServer['version'], $dbServer['extension']);
    }

    /**
     * @param array $dbServer
     *
     * @return void
     */
    public function show(array $dbServer): void
    {
        $this->set('dbServer', $dbServer);
        $this->render();
    }
}
