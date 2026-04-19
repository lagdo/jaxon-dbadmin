<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Lagdo\DbAdmin\App\Ajax\Base\PageComponent;
use Lagdo\DbAdmin\Support\Service\Admin\QueryHistory;
use Lagdo\DbAdmin\App\Ui\Command\AuditUiBuilder;

class HistoryPage extends PageComponent
{
    /**
     * @param AuditUiBuilder $auditUi
     * @param QueryHistory|null $queryHistory
     */
    public function __construct(private AuditUiBuilder $auditUi,
        private QueryHistory|null $queryHistory)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->queryHistory?->getLimit() ?? 10;
    }

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        return -1;
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $queries = $this->queryHistory?->getQueries($this->currentPage()) ?? [];
        return $this->auditUi->historyQueries($queries);
    }
}
