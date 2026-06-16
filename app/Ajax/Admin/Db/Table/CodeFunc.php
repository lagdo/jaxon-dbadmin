<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\Update;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;

use function count;
use function implode;
use function is_array;

class CodeFunc extends BaseComponent
{
    /**
     * @param EditUiBuilder  $editUi
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param string $title
     * @param string $query
     * @param array $buttons
     *
     * @return void
     */
    private function showQueryCodeDialog(string $title, string $query, array $buttons = []): void
    {
        // Show the query in a modal dialog.
        $title = $this->trans()->lang($title);
        $content = $this->editUi->sqlCodeElement($query);
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Close'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($query),
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
        $result = $this->db()->getInsertRowQuery($this->getCurrentTable(), [], $formValues);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->modal()->hide();

        $buttons = [[
            'title' => $this->trans()->lang('Back'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Insert::class)->showQueryForm($fromSelect, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for insert', $result['query'], $buttons);
    }

    /**
     * Show the update query
     *
     * @param int   $editId
     * @param array $rowIds
     * @param array $formValues
     *
     * @return void
     */
    public function showUpdateRowQuery(int $editId, array $rowIds, array $formValues): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $tableName = $this->getCurrentTable();
        $result = $this->db()->getUpdateRowQuery($tableName, $rowIds, $formValues);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->modal()->hide();

        $buttons = [[
            'title' => $this->trans()->lang('Back'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Update::class)->showQueryForm($editId, $rowIds, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for update', $result['query'], $buttons);
    }

    /**
     * Show the delete query
     *
     * @param int   $editId
     * @param array $rowIds
     *
     * @return void
     */
    public function showDeleteRowQuery(int $editId, array $rowIds): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $result = $this->db()->getDeleteRowQuery($this->getCurrentTable(), $rowIds);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->showQueryCodeDialog('SQL query for delete', $result['query']);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    public function showDropTableQuery(string $table): void
    {
        $result = $this->db()->getDropTableQueries($table);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $queries = implode("\n", $result['queries']);
        $this->showQueryCodeDialog('SQL query for table drop', $queries);
    }

    /**
     * @param string $view
     *
     * @return void
     */
    public function showDropViewQuery(string $view): void
    {
        $result = $this->db()->getDropViewQueries($view);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $queries = implode("\n", $result['queries']);
        $this->showQueryCodeDialog('SQL query for view drop', $queries);
    }
}
