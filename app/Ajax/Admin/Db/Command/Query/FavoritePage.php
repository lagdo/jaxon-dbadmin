<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Lagdo\DbAdmin\App\Ajax\Base\PageComponent;
use Lagdo\DbAdmin\Support\Service\Admin\QueryFavorite;
use Lagdo\DbAdmin\App\Ui\Command\AuditUiBuilder;

class FavoritePage extends PageComponent
{
    /**
     * @param AuditUiBuilder $auditUi
     * @param QueryFavorite|null $queryFavorite
     */
    public function __construct(private AuditUiBuilder $auditUi,
        private QueryFavorite|null $queryFavorite)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->queryFavorite?->getLimit() ?? 10;
    }

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        return $this->queryFavorite?->getQueryCount([]) ?? 0;
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $queries = $this->queryFavorite?->getQueries([], $this->currentPage()) ?? [];
        return $this->auditUi->favorites($queries);
    }
}
