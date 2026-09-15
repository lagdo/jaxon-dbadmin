<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Support\Service\Audit\QueryLogger;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;
use Lagdo\DbAdmin\Support\Service\Audit\AuditDatabase;

#[Exclude]
class Sidebar extends Component
{
    use ComponentDataTrait;

    /**
     * @param QueryLogger $queryLogger
     * @param AuditUiBuilder $uiBuider
     * @param AuditDatabase $db
     */
    public function __construct(private QueryLogger $queryLogger,
        private AuditUiBuilder $uiBuider, private AuditDatabase $db)
    {}

    /**
     * @return string
     */
    private function toggleButton(): string
    {
        $visible = $this->bag('dbadmin.audit')->get('sidebar.visible', true);
        return $this->uiBuider->sidebarToggleButton($visible);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return match($this->get('item', 'main')) {
            'toggle' => $this->toggleButton(),
            default => $this->uiBuider->sidebar($this->queryLogger->getCategories()),
        };
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        if ($this->get('item', 'main') !== 'main') {
            return;
        }

        $this->cl(Page\AppUser::class)->render();
        $serverInfo = $this->db->getServerInfo();
        $this->cl(Page\DbServer::class)->show($serverInfo);
    }

    /**
     * @param bool $visible
     *
     * @return void
     */
    public function toggle(bool $visible): void
    {
        $this->item('header')->visible($visible);
        $this->item('wrapper')->visible($visible);

        $this->set('item', 'toggle');
        $this->item('toggle')->render();
    }
}
