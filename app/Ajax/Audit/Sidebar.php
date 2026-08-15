<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Support\Service\Audit\QueryLogger;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;
use Lagdo\DbAdmin\Support\Service\Audit\AuditDatabase;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @param QueryLogger $queryLogger
     * @param AuditUiBuilder $uiBuider
     * @param AuditDatabase $db
     */
    public function __construct(private QueryLogger $queryLogger,
        private AuditUiBuilder $uiBuider, private AuditDatabase $db)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->sidebar($this->queryLogger->getCategories());
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Page\AppUser::class)->render();
        $serverInfo = $this->db->getServerInfo();
        $this->cl(Page\DbServer::class)->show($serverInfo);
    }
}
