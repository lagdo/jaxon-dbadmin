<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;
use Lagdo\DbAdmin\Support\Service\Admin\QueryHistory;
use Lagdo\DbAdmin\App\Ui\Command\AuditUiBuilder;

#[Exclude]
class History extends Component
{
    /**
     * @param AuditUiBuilder $auditUi
     * @param QueryHistory|null $queryHistory
     */
    public function __construct(private AuditUiBuilder $auditUi,
        private QueryHistory|null $queryHistory)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        $queries = $this->queryHistory?->getQueries() ?? [];
        return $this->auditUi->history($queries);
    }
}
