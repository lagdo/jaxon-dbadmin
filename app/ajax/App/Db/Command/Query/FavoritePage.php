<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\PageComponent;
use Lagdo\DbAdmin\Service\DbAdmin\QueryFavorite;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;

class FavoritePage extends PageComponent
{
    /**
     * @param LogUiBuilder $logUi
     * @param QueryFavorite|null $queryFavorite
     */
    public function __construct(private LogUiBuilder $logUi,
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
        return $this->logUi->favorites($queries);
    }
}
