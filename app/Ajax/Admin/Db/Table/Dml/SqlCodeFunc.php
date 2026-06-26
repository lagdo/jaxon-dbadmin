<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;

use function count;
use function implode;
use function is_array;

class SqlCodeFunc extends BaseComponent
{
    /**
     * @param EditUiBuilder  $editUi
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param string $title
     * @param QueryListDto $queryList
     * @param array $buttons
     * @param bool $hideModal
     *
     * @return void
     */
    private function showQueryCodeDialog(string $title, QueryListDto $queryList,
        array $buttons = [], bool $hideModal = false): void
    {
        // Show the error
        if($queryList->error !== null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($queryList->error);
            return;
        }

        if ($hideModal) {
            $this->modal()->hide();
        }

        // Show the query in a modal dialog.
        $queryCode = implode("\n", $queryList->queries);
        $title = $this->trans()->lang($title);
        $content = $this->editUi->sqlCodeElement($queryCode);
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Close'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($queryCode),
        ], ...$buttons];

        $this->modal()->show($title, $content, $buttons, $options);

        $this->setupSqlEditor($this->editUi->queryDivId());
    }

    /**
     * Show the insert query
     *
     * @param bool $fromSelect
     * @param array $formValues
     *
     * @return void
     */
    public function showInsertRowQuery(bool $fromSelect, array $formValues): void
    {
        // No specific options for inserts.
        $queryList = $this->db()->getInsertRowQuery($this->getCurrentTable(), [], $formValues);

        $buttons = [[
            'title' => $this->trans()->lang('Back'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(InsertFunc::class)->showQueryForm($fromSelect, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for insert', $queryList, $buttons, true);
    }

    /**
     * Show the update query
     *
     * @param int   $rowId
     * @param array $rowIdValues
     * @param array $formValues
     *
     * @return void
     */
    public function showUpdateRowQuery(int $rowId, array $rowIdValues, array $formValues): void
    {
        if(!is_array($rowIdValues['where'] ?? 0) ||
            count($rowIdValues['where']) === 0 || $rowId <= 0) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $tableName = $this->getCurrentTable();
        $queryList = $this->db()->getUpdateRowQuery($tableName, $rowIdValues, $formValues);

        $buttons = [[
            'title' => $this->trans()->lang('Back'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(UpdateFunc::class)->showQueryForm($rowId, $rowIdValues, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for update', $queryList, $buttons, true);
    }

    /**
     * Show the delete query
     *
     * @param int   $rowId
     * @param array $rowIdValues
     *
     * @return void
     */
    public function showDeleteRowQuery(int $rowId, array $rowIdValues): void
    {
        if(!is_array($rowIdValues['where'] ?? 0) ||
            count($rowIdValues['where']) === 0 || $rowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $queryList = $this->db()->getDeleteRowQuery($this->getCurrentTable(), $rowIdValues);

        // Show the query in a modal dialog.
        $this->showQueryCodeDialog('SQL query for delete', $queryList);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    public function showDropTableQuery(string $table): void
    {
        $queryList = $this->db()->getDropTableQueries($table);

        // Show the query in a modal dialog.
        $this->showQueryCodeDialog('SQL query for table drop', $queryList);
    }

    /**
     * @param string $view
     *
     * @return void
     */
    public function showDropViewQuery(string $view): void
    {
        $queryList = $this->db()->getDropViewQueries($view);

        // Show the query in a modal dialog.
        $this->showQueryCodeDialog('SQL query for view drop', $queryList);
    }
}
