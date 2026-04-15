<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ui\Select\ResultUiBuilder;

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
     * @var int|null
     */
    private int|null $_count = null;

    /**
     * The constructor
     *
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     */
    public function __construct(protected ResultUiBuilder $resultUi)
    {}

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        // Save the count value in the $_count attribute.

        $options = $this->getOptions();
        if (!($options['total'] ?? true)) {
            // Do not query the total number of items.
            return $this->_count = -1;
        }

        $table = $this->getCurrentTable();
        return $this->_count = $this->db()->countSelect($table, $options);
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
            $editItemId = $this->bagValueKey($editId);

            $editIds[$editItemId] = $row['ids'];

            $row['editId'] = 0;
            $row['menu'] = '';
            // The row is editable when the editId value is greated than 0.
            if (count($row['ids']['where'] ?? []) > 0) {
                $row['editId'] = $editId;
                $row['editItemId'] = $editItemId;
                $row['menu'] = $this->getRowMenu($editId);
            }

            return $row;
        }, $results['rows']);

        $this->bag($this->tabBag('dbadmin.edit'))->set('row.ids', $editIds);

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
        $options = $this->getOptions();
        $results = $this->db()->execSelect($this->getCurrentTable(), $options);

        // The 'message' key is set when an error occurs, or when the query returns no data.
        if (isset($results['message'])) {
            $this->set('duration', null);
            return $results['message'];
        }

        $this->set('duration', $results['duration']);

        return $this->resultUi->resultSet($results['headers'], $this->rows($results));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(QueryText::class)->refresh();
        $this->cl(Duration::class)->update($this->get('duration'));

        $duration =  $this->get('duration', null);
        ($duration === null) || $this->_count > 0 && $this->_count <= $this->limit() ?
            $this->cl(GotoPage::class)->clear() :
            $this->cl(GotoPage::class)->set('page', $this->currentPage())->render();

        // Reset the count value.
        $this->_count = null;
    }
}
