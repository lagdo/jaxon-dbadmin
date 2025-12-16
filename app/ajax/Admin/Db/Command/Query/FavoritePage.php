<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\PageComponent;
use Lagdo\DbAdmin\Db\Service\Admin\QueryFavorite;
use Lagdo\DbAdmin\Ui\Command\AuditUiBuilder;

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
        return $this->queryFavorite->getLimit();
    }

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        return $this->queryFavorite->getQueryCount([]);
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $queries = $this->queryFavorite->getQueries([], $this->currentPage());
        return $this->auditUi->favorites($queries);
    }
}
