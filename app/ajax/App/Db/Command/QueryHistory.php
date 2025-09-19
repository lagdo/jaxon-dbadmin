<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Service\LogWriter;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

class QueryHistory extends Component
{
    /**
     * @param QueryUiBuilder $queryUi
     * @param LogWriter|null $queryLogger
     */
    public function __construct(private QueryUiBuilder $queryUi,
        private LogWriter|null $queryLogger)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        $commands = !$this->queryLogger ? [] : $this->queryLogger->getHistoryCommands();
        return $this->queryUi->history($commands);
    }
}
