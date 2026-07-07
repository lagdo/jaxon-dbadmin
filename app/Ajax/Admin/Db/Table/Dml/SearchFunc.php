<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;

use function count;
use function strtolower;
use function str_replace;
use function trim;

/**
 * This class provides the search feature for autocomplete on foreign columns.
 */
#[Databag('dbadmin.builder')]
class SearchFunc extends FuncComponent
{
    use QueryBuilderTrait;

    /**
     * @param EditUiBuilder  $editUi
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param string $table
     * @param string $idColumn
     * @param string $labelColumn
     * @param string $sourceColumn
     * @param string $searchValue
     *
     * @return void
     */
    public function search(string $table, string $idColumn, string $labelColumn,
        string $sourceColumn, string $searchValue): void
    {
        $searchListId = $this->editUi->searchListId($sourceColumn);
        $searchValue = strtolower(trim($searchValue));
        if ($searchValue === '') {
            $this->response()->je($searchListId)->hidePopover();
            return;
        }

        $length = $this->getParamValue('length');
        $filters = [[
            'column' => $labelColumn,
            'operator' => 'LIKE %%',
            'operand' => $searchValue,
        ]];
        $options = [
            'limit' => 10,
            'total' => false,
            'length' => $length,
            'columns' => [[
                'func' => '',
                'column' => $idColumn,
            ], [
                'func' => '',
                'column' => $labelColumn,
            ]],
            'filters' => $filters,
            'sorters' => [[
                'desc' => false,
                'column' => $labelColumn,
            ]],
            'foreigns' => false,
        ];
        $select = $this->db()->getSelectParams($table, $options);
        if (count($select->columns) !== 2) {
            // Todo: display an error message.
            return;
        }

        // Apply SQL functions to the label column.
        $columnName = $select->columns[1];
        $filter = $select->filters[0];
        $select->filters[0] = str_replace($columnName, "LOWER($columnName)", $filter);
        $select->columns[1] = "SUBSTR($columnName, 1, $length)";
        $result = $this->db()->execSelect($select);
        if ($result->error !== null || count($result->rowsets) === 0) {
            // Todo: display an error message.
            return;
        }

        $rowset = $result->rowsets[0];
        $searchListHtml = $this->editUi->getAutocompleteList($rowset, $sourceColumn);

        $this->response()->html($searchListId, $searchListHtml);
        $this->response()->je($searchListId)->showPopover();
    }
}
