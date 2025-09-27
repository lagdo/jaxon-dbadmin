<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Service\DbAdmin\QueryHistory;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

/**
 * @exclude
 */
class History extends Component
{
    /**
     * @param QueryUiBuilder $queryUi
     * @param QueryHistory|null $queryHistory
     */
    public function __construct(private QueryUiBuilder $queryUi,
        private QueryHistory|null $queryHistory)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        $commands = !$this->queryHistory ? [] : $this->queryHistory->getQueries();
        return $this->queryUi->history($commands);
    }
}
