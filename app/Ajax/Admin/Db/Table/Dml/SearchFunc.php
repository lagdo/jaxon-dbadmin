<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;

use function count;
use function strtolower;
use function trim;

/**
 * This class provides the search feature for autocomplete on foreign columns.
 */
#[Databag('dbadmin.builder')]
class SearchFunc extends FuncComponent
{
    /**
     * @param EditUiBuilder  $editUi
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param string $table
     * @param string $column
     * @param string $search
     *
     * @return void
     */
    public function search(string $table, string $column, string $search): void
    {
        $searchListId = $this->editUi->searchListId($column);
        $search = strtolower(trim($search));
        if ($search === '') {
            $this->response()->je($searchListId)->hidePopover();
            return;
        }

        $options = [
            'limit' => 10,
            'total' => false,
            'length' => $this->getParamValue('length'),
            'foreigns' => false,
        ];
        $result = $this->db()->searchInForeignColumn($table, $column, $search, $options);
        if ($result->error !== null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }
        if (count($result->rowsets) === 0) {
            return;
        }

        $rowset = $result->rowsets[0];
        $searchListHtml = $this->editUi->getAutocompleteList($rowset, $column);

        $this->response()->html($searchListId, $searchListHtml);
        $this->response()->je($searchListId)->showPopover();
    }
}
