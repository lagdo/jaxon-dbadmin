<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query;

/**
 * Show the changes and queries on a table.
 */
class QueryFunc extends Column\FuncComponent
{
    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-data-form';

    /**
     * @param array  $values      The table values
     *
     * @return void
     */
    public function changes(array $values): void
    {
        $title = 'Changes in table ' . $this->getTableName();
        $content = $this->columnUi->changes($this->getTableColumns());
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];

        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param array $formValues
     *
     * @return array
     */
    private function options(array $formValues): array
    {
        $formValues['hasAutoIncrement'] = isset($formValues['hasAutoIncrement']);
        return $formValues;
    }

    /**
     * Show the queries to create the table
     *
     * @param array $formValues
     *
     * @return void
     */
    public function createTable(array $formValues): void
    {
        $options = $this->options($formValues);
        $result = $this->db()->getCreateTableQueries($options, $this->getTableColumns());
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        $queryText = implode(";\n\n", $result['queries']) . ";\n";
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
     * @param array $formValues
     *
     * @return void
     */
    public function alterTable(array $formValues): void
    {
        $table = $this->getTableName();
        $options = $this->options($formValues);
        $result = $this->db()->getAlterTableQueries($table, $options, $this->getTableColumns());
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        $queryText = implode(";\n\n", $result['queries']) . ";\n";
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
