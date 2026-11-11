<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;

#[Exclude]
class ImportResult extends Component
{
    /**
     * @param QueryUiBuilder $queryUi   The HTML UI builder
     */
    public function __construct(protected QueryUiBuilder $queryUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->queryUi->results($this->get('result'));
    }
}
