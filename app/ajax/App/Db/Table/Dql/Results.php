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
        $table = $this->getTableName();
        $options = $this->getOptions(false); // Do not take the page.
        return $this->db()->countSelect($table, $options);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Save the current page in the databag
        $this->savePageNumber($this->currentPage());

        // Select options
        $options = $this->getOptions(true);
        $results = $this->db()->execSelect($this->getTableName(), $options);
        if (isset($results['message'])) {
            return $results['message'];
        }

        $this->stash()->set('select.duration', $results['duration']);

        // The 'message' key is set when an error occurs, or when the query returns no data.
        return $this->selectUi->results($results['headers'], $results['rows']);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(QueryText::class)->refresh();
        $this->cl(Duration::class)->render();
    }
}
