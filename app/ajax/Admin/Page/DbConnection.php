<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\MenuComponent;

class DbConnection extends MenuComponent
{
    /**
     * @var string
     */
    private $server = '';

    /**
     * @var string
     */
    private $user = '';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->dbConnection($this->server, $this->user);
    }

    /**
     * @param string $server
     * @param string $user
     *
     * @return void
     */
    #[Exclude]
    public function show(string $server, string $user): void
    {
        $this->server = $server;
        $this->user = $user;
        $this->render();
    }
}
