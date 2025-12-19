<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use function array_map;
use function count;

/**
 * This class provides select query features on tables.
 */
class ResultSet extends PageComponent
{
    use QueryTrait;
    use RowMenuTrait;

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
        $editIds = [];
        $rows = array_map(function($row) use(&$editId, &$editIds): array {
            $editId++; // The edit ids start from 1.
            $editIds[$this->bagEntryName($editId)] = $row['ids'];

            // The row is editable when the editId value is greated than 0.
            $editable = count($row['ids']['where'] ?? []) > 0;
            $row['editId'] = $editable ? $editId : 0;
            $row['menu'] = $editable ? $this->getRowMenu($editId) : '';

            return $row;
        }, $results['rows']);

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

        // The 'message' key is set when an error occurs, or when the query returns no data.
        $this->noResult = isset($results['message']);
        if ($this->noResult) {
            return $results['message'];
        }

        $this->stash()->set('select.duration', $results['duration']);

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
