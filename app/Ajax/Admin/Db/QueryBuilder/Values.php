<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\GotoPage;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;

/**
 * This class provides select query features on tables.
 */
class Values extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->optionsUi->optionsValues($this->getBuilderParams());
    }

    /**
     * @return void
     */
    private function clearResults(): void
    {
        $this->cl(Duration::class)->clear();
        $this->cl(GotoPage::class)->clear();
        $this->cl(ResultSet::class)->clear();
    }

    /**
     * Change the query options
     *
     * @param int $limit
     *
     * @return void
     */
    public function saveSelectLimit(int $limit): void
    {
        $this->saveParamValue('limit', $limit);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
        $this->render();
        // Clear the result components
        $this->clearResults();
    }

    /**
     * Change the query options
     *
     * @param bool $total
     *
     * @return void
     */
    public function saveSelectTotal(bool $total): void
    {
        $this->saveParamValue('total', $total);

        $this->render();
        // Clear the result components
        $this->clearResults();
    }

    /**
     * Change the query options
     *
     * @param int $length
     *
     * @return void
     */
    public function saveTextLength(int $length): void
    {
        // Fix the text length value.
        $this->saveParamValue('length', $length > 0 ? $length : 100);

        $this->render();
        // Clear the result components
        $this->clearResults();
    }
}
