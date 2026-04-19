<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Component;
use Lagdo\DbAdmin\App\Ui\Command\AuditUiBuilder;

#[Exclude]
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
