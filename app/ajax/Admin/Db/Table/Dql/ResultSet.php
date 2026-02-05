<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Select\ResultUiBuilder;
use Lagdo\DbAdmin\Ui\UiBuilder;

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
     * @var float|null
     */
    private float|null $duration;

    /**
     * The constructor
     *
     * @param DbFacade          $db         The facade to database functions
     * @param UiBuilder         $ui         The HTML UI builder
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     * @param Translator        $trans
     */
    public function __construct(protected DbFacade $db, protected UiBuilder $ui,
        protected ResultUiBuilder $resultUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function count(): int
    {
        $options = $this->getOptions();
        if (!($options['total'] ?? true)) {
            // Do not query the total number of items.
            return -1;
        }

        $table = $this->getCurrentTable();
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
            $this->duration = null;
            return $results['message'];
        }

        $this->duration = $results['duration'];

        return $this->resultUi->resultSet($results['headers'], $this->rows($results));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(QueryText::class)->refresh();
        $this->cl(Duration::class)->update($this->duration);
    }
}
