<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Export;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Component;
use Lagdo\DbAdmin\App\Ui\Command\AuditUiBuilder;
use Lagdo\DbAdmin\Support\Service\Admin\AuditConnection;

#[Export(['render'])]
class Favorite extends Component
{
    /**
     * @var AuditConnection
     */
    #[Inject]
    private AuditConnection $audit;

    /**
     * @param AuditUiBuilder $auditUi
     */
    public function __construct(private AuditUiBuilder $auditUi)
    {}

    /**
     * @inheritDoc
     */
    protected function overrides(): string
    {
        return Queries::class;
    }

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->auditUi->favorite($this->audit->queryHistoryEnabled());
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(FavoritePage::class)->page();
    }
}
