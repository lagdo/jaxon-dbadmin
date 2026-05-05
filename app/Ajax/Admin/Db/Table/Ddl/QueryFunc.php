<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query;

use function count;
use function implode;

/**
 * Show the queries on a table.
 */
class QueryFunc extends Column\FuncComponent
{
    /**
     * @param array $tableInputs
     *
     * @return array
     */
    private function tableInputs(array $tableInputs): array
    {
        return [
            ...$tableInputs,
            'hasAutoIncrement' => isset($tableInputs['hasAutoIncrement']),
            'setComment' => isset($tableInputs['setComment']),
        ];
    }

    /**
     * @param array $queries
     *
     * @return string
     */
    private function queryText(array $queries): string
    {
        return count($queries) === 0 ? '' : implode(";\n", $queries) . ";\n";
    }

    /**
     * Show the queries to create the table
     *
     * @param array $tableInputs
     *
     * @return void
     */
    public function create(array $tableInputs): void
    {
        $tableInputs = $this->tableInputs($tableInputs);
        $columnsInputs = $this->getColumnInputs();
        $result = $this->db()->getCreateTableQueries($tableInputs, $columnsInputs);
        // Show the error
        if (isset($result['error'])) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        $queryText = $this->queryText($result['queries']);
        $title = $this->trans()->lang('Queries to create a new table');
        $content = $this->columnUi->sqlCodeElement($queryText);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($queryText),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->setupSqlEditor($this->columnUi->getQueryDivId());
    }

    /**
     * Show the queries to alter the table
     *
     * @param array $tableInputs
     *
     * @return void
     */
    public function alter(array $tableInputs): void
    {
        $table = $this->getCurrentTable();
        $tableInputs = $this->tableInputs($tableInputs);
        $columnsInputs = $this->getColumnInputs();
        $result = $this->db()->getAlterTableQueries($table, $tableInputs, $columnsInputs);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        $queryText = $this->queryText($result['queries']);
        $title = $this->trans()->lang('Queries to create a new table');
        $content = $this->columnUi->sqlCodeElement($queryText);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($queryText),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->setupSqlEditor($this->columnUi->getQueryDivId());
    }
}
