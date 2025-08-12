<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

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
     * @exclude
     *
     * @param string $server
     * @param string $user
     *
     * @return void
     */
    public function show(string $server, string $user): void
    {
        $this->server = $server;
        $this->user = $user;
        $this->render();
    }
}
