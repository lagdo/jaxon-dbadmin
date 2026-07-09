<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\QueryEditor;

use function array_map;
use function count;
use function implode;
use function is_string;

/**
 * Show the changes and queries on a table.
 */
class SqlCodeFunc extends Column\FuncComponent
{
    /**
     * @param array $tableInputs
     *
     * @return void
     */
    public function showCreateChanges(array $tableInputs): void
    {
        $this->setCurrentTable('');

        $tableDto = $this->tableDto();
        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $title = 'Values for the new table';
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->createValues($tableDto);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];
        $this->modal()->show($title, $content ?: '&nbsp;', $buttons);
    }

    /**
     * @param array $tableInputs
     *
     * @return void
     */
    public function showAlterChanges(array $tableInputs): void
    {
        $tableName = $this->getCurrentTable();
        $tableDto = $this->tableDto();
        if ($tableDto->status->name === '') {
            $this->alert()
                ->title('Error')
                ->error("Unable to find the '$tableName' table.");
            return;
        }

        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $title = "Changes in the table $tableName";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->alterValues($tableDto);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];
        $this->modal()->show($title, $content ?: '&nbsp;', $buttons);
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
    public function showCreateQueries(array $tableInputs): void
    {
        $this->setCurrentTable('');

        $tableDto = $this->tableDto();
        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $result = $this->driver()->getCreateTableQueries($tableDto);
        // Show the error
        if ($result->error !== null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        $queryCode = $this->getQueryCode($result->queries);
        $title = $this->trans()->lang('Queries to create a new table');
        $content = $this->columnUi->sqlCodeElement($queryCode);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(QueryEditor::class)->database($queryCode),
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
    public function showAlterQueries(array $tableInputs): void
    {
        $tableName = $this->getCurrentTable();
        $tableDto = $this->tableDto();
        if ($tableDto->status->name === '') {
            $this->alert()
                ->title('Error')
                ->error("Unable to find the '$tableName' table.");
            return;
        }

        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $result = $this->driver()->getAlterTableQueries($tableDto);
        // Show the error
        if($result->error !== null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        $queryCode = $this->getQueryCode($result->queries);
        $title = $this->trans()->lang("Queries to alter the '$tableName' table");
        $content = $this->columnUi->sqlCodeElement($queryCode);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(QueryEditor::class)->database($queryCode),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->setupSqlEditor($this->columnUi->getQueryDivId());
    }
}
