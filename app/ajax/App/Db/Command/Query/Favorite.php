<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\App\Db\Component;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;

class Favorite extends Component
{
    /**
     * @param LogUiBuilder $logUi
     */
    public function __construct(private LogUiBuilder $logUi)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->logUi->favorite();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(FavoritePage::class)->page();
    }
}
