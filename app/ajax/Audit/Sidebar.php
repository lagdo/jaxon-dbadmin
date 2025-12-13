<?php

namespace Lagdo\DbAdmin\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Service\Logging\QueryLogger;
use Lagdo\DbAdmin\Ui\Logging\LogUiBuilder;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @param QueryLogger $queryLogger
     * @param LogUiBuilder $uiBuider;
     */
    public function __construct(private QueryLogger $queryLogger,
        private LogUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->sidebar($this->queryLogger->getCategories());
    }
}
