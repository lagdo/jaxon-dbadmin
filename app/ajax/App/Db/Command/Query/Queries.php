<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Ui\Command\AuditUiBuilder;

/**
 * User queries container (history and favorites)
 */
class Queries extends Component
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
        return $this->auditUi->queries();
    }
}
