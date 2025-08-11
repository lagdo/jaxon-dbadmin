<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

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
            $this->html->results($results['headers'], $results['rows']);
    }
}
