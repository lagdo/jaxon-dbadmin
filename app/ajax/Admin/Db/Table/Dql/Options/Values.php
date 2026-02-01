<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;

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
        $options = $this->getSelectBag('options', []);
        return $this->optionsUi->optionsValues($options);
    }

    /**
     * @return void
     */
    private function clearResults(): void
    {
        $this->cl(Duration::class)->clear();
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
        // Select options
        $options = $this->getSelectBag('options');
        $options['limit'] = $limit;
        $this->setSelectBag('options', $options);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
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
        // Select options
        $options = $this->getSelectBag('options');
        $options['total'] = $total;
        $this->setSelectBag('options', $options);

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
        // Select options
        $options = $this->getSelectBag('options');
        $options['length'] = $length;
        $this->setSelectBag('options', $options);

        // Clear the result components
        $this->clearResults();
    }
}
