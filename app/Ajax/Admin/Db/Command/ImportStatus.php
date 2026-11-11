<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\Command\ImportUiBuilder;

#[Exclude]
class ImportStatus extends Component
{
    use ComponentDataTrait;

    /**
     * @param ImportUiBuilder $importUi
     */
    public function __construct(private ImportUiBuilder $importUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->importUi->importStatus($this->get('import'));
    }
}
