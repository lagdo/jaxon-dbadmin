<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;

// use Jaxon\App\Component\Pagination;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\Ui\Tab;

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
        // Todo: Automate this in the Jaxon library.
        // $this->cl(Pagination::class)
        //     ->item(Tab::current() . '::' . $this->rq(ResultSet::class)->_class())
        //     ->clear();
        $this->response()->clear(Tab::id('jaxon-dbadmin-resulset-pagination'));
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
