<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\CallableClass;

use function count;
use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Sorting extends CallableClass
{
    /**
     * The sorting form div id
     *
     * @var string
     */
    private $sortingFormId = 'adminer-table-select-sorting-form';

    /**
     * Default select options
     *
     * @var array
     */
    private $selectOptions = ['limit' => 50, 'text_length' => 100];

    /**
     * Change the query sorting
     *
     * @return void
     */
    public function edit()
    {
        // Select options
        $table = $this->bag('dbadmin')->get('db.table.name');
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $selectData = $this->db->getSelectData($table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->sortingFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit order';
        $content = $this->ui->editQuerySorting($this->sortingFormId,
            $selectData['options']['sorting'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveSorting(pm()->form($this->sortingFormId)),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->response->addCommand('dbadmin.new.index.set', [
            'count' => count($selectData['options']['sorting']['values']),
        ]);
    }

    /**
     * Change the query sorting
     *
     * @param array  $formValues  The form values
     *
     * @return void
     */
    public function save(array $formValues)
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['order'] = $formValues['order'] ?? [];
        $options['desc'] = $formValues['desc'] ?? [];
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table.name');
        $selectData = $this->db->getSelectData($table, $options);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(Query::class)->show($selectData['query']);
    }
}
