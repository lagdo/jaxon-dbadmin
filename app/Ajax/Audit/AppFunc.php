<?php

namespace Lagdo\DbAdmin\App\Ajax\Audit;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Support\Service\Audit\AuditDatabase;

#[Databag('dbadmin.audit')]
class AppFunc extends FuncComponent
{
    /**
     * @param AuditDatabase $auditDb
     */
    public function __construct(private AuditDatabase $auditDb)
    {}

    /**
     * @return void
     */
    public function start(): void
    {
        $this->cl(Page\AppUser::class)->render();
        $serverInfo = $this->auditDb->getServerInfo();
        $this->cl(Page\DbServer::class)->show($serverInfo);

        $this->cl(Commands::class)->page();
    }
}
