<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\PageComponent;

/**
 * This class provides select query features on tables.
 */
class Results extends PageComponent
{
    use QueryTrait;

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        return $this->db()->countSelect($this->getTableName(), $this->getOptions());
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Select options
        $options = $this->getOptions();
        $options['page'] = $this->currentPage();

        $results = $this->db()->execSelect($this->getTableName(), $options);
        return $results['message'] ??
            $this->ui()->selectResults($results['headers'], $results['rows']);
    }
}
