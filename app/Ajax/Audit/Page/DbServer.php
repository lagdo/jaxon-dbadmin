<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit\Page;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;

#[Exclude]
class DbServer extends Component
{
    use ComponentDataTrait;

    /**
     * @param AuditUiBuilder $uiBuider
     */
    public function __construct(private AuditUiBuilder $uiBuider)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $dbServer = $this->get('dbServer');
        return $this->uiBuider->dbServer($dbServer);
    }

    /**
     * @param array $dbServer
     *
     * @return void
     */
    public function show(array $dbServer): void
    {
        $this->set('dbServer', $dbServer);
        $this->render();
    }
}
