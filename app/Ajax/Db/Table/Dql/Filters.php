<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\CallableClass;

use function count;
use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Filters extends CallableClass
{
    /**
     * The filters form div id
     *
     * @var string
     */
    private $filtersFormId = 'adminer-table-select-filters-form';

    /**
     * Change the query filters
     *
     * @return void
     */
    public function edit()
    {
        // Select options
        $table = $this->bag('dbadmin')->get('db.table.name');
        $options = $this->bag('dbadmin.select')->get('options');
        $selectData = $this->db->getSelectData($table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->filtersFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit filters';
        $content = $this->ui->editQueryFilters($this->filtersFormId,
            $selectData['options']['filters'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(pm()->form($this->filtersFormId)),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->response->addCommand('dbadmin.new.index.set', [
            'count' => count($selectData['options']['filters']['values']),
        ]);
    }

    /**
     * Change the query filters
     *
     * @param array  $formValues  The form values
     *
     * @return void
     */
    public function save(array $formValues)
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options');
        $options['where'] = $formValues['where'] ?? [];
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table.name');
        $selectData = $this->db->getSelectData($table, $options);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(Query::class)->show($selectData['query']);
    }
}
