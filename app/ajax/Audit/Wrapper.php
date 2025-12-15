<?php

namespace Lagdo\DbAdmin\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ui\AuditUiBuilder;

#[Exclude]
class Wrapper extends Component
{
    /**
     * @param AuditUiBuilder $uiBuider;
     */
    public function __construct(private AuditUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->uiBuider->wrapper();
    }
}
