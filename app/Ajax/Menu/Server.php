<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Ui\MenuBuilder;

class Server extends Component
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
     * @param MenuBuilder $ui
     */
    public function __construct(private MenuBuilder $ui)
    {}

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
    public function update(string $server, string $user)
    {
        $this->server = $server;
        $this->user = $user;
        $this->refresh();
    }
}
