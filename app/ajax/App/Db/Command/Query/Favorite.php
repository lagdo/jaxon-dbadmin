<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\App\Db\Component;
use Lagdo\DbAdmin\Ui\Command\AuditUiBuilder;

class Favorite extends Component
{
    /**
     * @param AuditUiBuilder $auditUi
     */
    public function __construct(private AuditUiBuilder $auditUi)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->auditUi->favorite();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(FavoritePage::class)->page();
    }
}
