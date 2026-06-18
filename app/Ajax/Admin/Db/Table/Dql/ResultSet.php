<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ui\Select\ResultUiBuilder;
use Lagdo\DbAdmin\Support\Driver\UiDto\RowsetDto;

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
     * @param RowsetDto $rowset
     *
     * @return array
     */
    private function rows(RowsetDto $rowset): array
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
        }, $rowset->rows);

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
        $result = $this->db()->execSelect($this->getCurrentTable(), $options);

        if ($result->error !== null) {
            $this->set('duration', null);
            return $result->error;
        }

        $this->set('duration', $result->duration);
        // The message field is set when the query returned no rows.
        if (count($result->rowsets) === 0) {
            return $result->message;
        }

        $rowset = $result->rowsets[0];
        return $this->resultUi->resultSet($rowset->headers, $this->rows($rowset));
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
