<?php

namespace Lagdo\DbAdmin\Ajax\Log;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ui\Logging\LogUiBuilder;

#[Exclude]
class Wrapper extends Component
{
    /**
     * @param LogUiBuilder $uiBuider;
     */
    public function __construct(private LogUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->wrapper();
    }
}
