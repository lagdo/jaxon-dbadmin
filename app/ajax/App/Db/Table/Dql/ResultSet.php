<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use function array_map;
use function count;

/**
 * This class provides select query features on tables.
 */
class ResultSet extends PageComponent
{
    use QueryTrait;

    /**
     * @var bool
     */
    private bool $noResult = false;

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
     * @param array $results
     *
     * @return array
     */
    private function rows(array $results): array
    {
        $editId = 0;
        $editIds = [[]]; // The first entry is an empty array
        $rows = array_map(function($row) use(&$editId, &$editIds): array {
            $editId++;
            $editIds[] = $row['ids'];
            // Id the row is editable, then the editId value is greated than 0.
            $row['editId'] = count($row['ids']['where'] ?? []) > 0 ? $editId : 0;

            return $row;
        }, $results['rows']);

        // Ids to edit rows.
        $this->bag('dbadmin.row.edit')->set('row.ids', $editIds);

        return $rows;
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

        $this->noResult = isset($results['message']);
        if ($this->noResult) {
            return $results['message'];
        }

        $this->stash()->set('select.duration', $results['duration']);

        // The 'message' key is set when an error occurs, or when the query returns no data.
        return $this->selectUi->resultSet($results['headers'], $this->rows($results));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(QueryText::class)->refresh();
        $this->noResult ? $this->cl(Duration::class)->clear() :
            $this->cl(Duration::class)->render();
    }
}
