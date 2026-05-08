<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Query;

use function array_map;
use function count;
use function implode;
use function is_string;

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
    private function getQueryCode(array $queries): string
    {
        if (count($queries) === 0) {
            return '';
        }

        $queries = array_map(function(array|string $query) {
            if (is_string($query)) {
                return $query;
            }

            [$type, $subQueries] = $query;
            return "-- Type: $type\n" . implode(";\n", $subQueries) . ";\n-- End: $type";
        }, $queries);

        return implode(";\n", $queries) . ";\n";
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

        $queryCode = $this->getQueryCode($result['queries']);
        $title = $this->trans()->lang('Queries to create a new table');
        $content = $this->columnUi->sqlCodeElement($queryCode);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($queryCode),
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

        $queryCode = $this->getQueryCode($result['queries']);
        $title = $this->trans()->lang('Queries to create a new table');
        $content = $this->columnUi->sqlCodeElement($queryCode);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($queryCode),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->setupSqlEditor($this->columnUi->getQueryDivId());
    }
}
