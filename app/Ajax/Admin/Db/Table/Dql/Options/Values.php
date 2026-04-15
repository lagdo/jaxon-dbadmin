<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options;

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
        $options = $this->getSelectBag('options', []);
        return $this->optionsUi->optionsValues($options);
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
        // Select options
        $options = $this->getSelectBag('options');
        $options['limit'] = $limit;
        $this->setSelectBag('options', $options);

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
        // Select options
        $options = $this->getSelectBag('options');
        $options['total'] = $total;
        $this->setSelectBag('options', $options);

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
        // Select options
        $options = $this->getSelectBag('options');
        // Fix the text length value.
        $options['length'] = $length > 0 ? $length : 100;
        $this->setSelectBag('options', $options);

        $this->render();
        // Clear the result components
        $this->clearResults();
    }
}
