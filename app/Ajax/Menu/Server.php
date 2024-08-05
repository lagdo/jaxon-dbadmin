<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\MenuComponent;

class Server extends MenuComponent
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
        return $this->ui->serverInfo($this->server, $this->user);
    }

    /**
     * @exclude
     *
     * @param string $server
     * @param string $user
     *
     * @return void
     */
    public function showServer(string $server, string $user)
    {
        $this->server = $server;
        $this->user = $user;
        $this->render();
    }
}
