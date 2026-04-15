<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Support\Service\Audit\QueryLogger;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @param QueryLogger $queryLogger
     * @param AuditUiBuilder $uiBuider;
     */
    public function __construct(private QueryLogger $queryLogger,
        private AuditUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->sidebar($this->queryLogger->getCategories());
    }
}
