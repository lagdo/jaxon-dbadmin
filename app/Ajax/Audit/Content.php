<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;

#[Exclude]
class Content extends Component
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
        return $this->uiBuider->content();
    }
}
