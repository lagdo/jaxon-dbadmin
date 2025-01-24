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
        $table = $this->bag('dbadmin')->get('db.table.name');
        return $this->db()->countSelect($table, $this->getOptions());
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Select options
        $options = $this->getOptions();
        $options['page'] = $this->currentPage();

        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db()->execSelect($table, $options);
        return $results['message'] ??
            $this->ui()->selectResults($results['headers'], $results['rows']);
    }
}
