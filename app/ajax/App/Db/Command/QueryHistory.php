<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Command\LoggingService;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

class QueryHistory extends Component
{
    /**
     * @param QueryUiBuilder $queryUi
     * @param LoggingService $logging
     */
    public function __construct(private QueryUiBuilder $queryUi,
        private LoggingService $logging)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        $commands = $this->logging->getHistoryCommands();
        return $this->queryUi->history($commands);
    }
}
