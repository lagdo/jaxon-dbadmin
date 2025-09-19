<?php

namespace Lagdo\DbAdmin\Ajax\Log;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Service\LogReader;
use Lagdo\DbAdmin\Ui\Log\LogUiBuilder;

/**
 * @exclude
 */
class Sidebar extends Component
{
    /**
     * @param LogReader $logReader
     * @param LogUiBuilder $uiBuider;
     */
    public function __construct(private LogReader $logReader,
        private LogUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->sidebar($this->logReader->getCategories());
    }
}
