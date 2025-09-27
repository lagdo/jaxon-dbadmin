<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

/**
 * User queries container (history and favorites)
 */
class Queries extends Component
{
    /**
     * @param QueryUiBuilder $queryUi
     */
    public function __construct(private QueryUiBuilder $queryUi)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->queryUi->queries();
    }
}
