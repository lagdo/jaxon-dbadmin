<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ui\Select\ResultUiBuilder;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectRowsetDto;

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
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     */
    public function __construct(protected ResultUiBuilder $resultUi)
    {}

    /**
     * @return SelectDqDto
     */
    private function select(): SelectDqDto
    {
        return $this->db()->getSelectParams($this->getCurrentTable(), $this->getOptions());
    }

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

        return $this->_count = $this->db()->countSelect($this->select());
    }

    /**
     * @param SelectRowsetDto $rowset
     *
     * @return array
     */
    private function rows(SelectRowsetDto $rowset): array
    {
        $rowId = 0;
        foreach ($rowset->rows as $row) {
            $row->bagId = $this->bagValueKey(++$rowId); // The edit ids start from 1.
            $row->rowMenu = $this->getRowMenu($rowId, $row);
        }

        return $rowset->rows;
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Save the current page in the databag
        $this->savePageNumber($this->currentPage());

        $result = $this->db()->execSelect($select = $this->select());

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
        $duration === null || $this->_count > 0 && $this->_count <= $this->limit() ?
            $this->cl(GotoPage::class)->clear() :
            $this->cl(GotoPage::class)->set('page', $this->currentPage())->render();

        // Reset the count value.
        $this->_count = null;
    }
}
