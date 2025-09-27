<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Service\DbAdmin\QueryHistory;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;

/**
 * @exclude
 */
class History extends Component
{
    /**
     * @param LogUiBuilder $logUi
     * @param QueryHistory|null $queryHistory
     */
    public function __construct(private LogUiBuilder $logUi,
        private QueryHistory|null $queryHistory)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        $queries = !$this->queryHistory ? [] : $this->queryHistory->getQueries();
        return $this->logUi->history($queries);
    }
}
