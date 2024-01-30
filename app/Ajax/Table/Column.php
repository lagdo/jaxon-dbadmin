<?php

namespace Lagdo\DbAdmin\App\Ajax\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use function Jaxon\jq;
use function Jaxon\pm;
use function sprintf;

/**
 * When creating or modifying a table, this class
 * provides CRUD features on table columns.
 * It does not persist data. It only updates the UI.
 */
class Column extends CallableClass
{
    /**
     * The form id
     */
    protected $formId = 'adminer-table-form';

    /**
     * Insert a new HTML element before a given target
     *
     * @param string $target      The target element
     * @param string $id          The new element id
     * @param string $class       The new element class
     * @param string $content     The new element content
     * @param array  $attrs       The new element attributes
     *
     * @return void
     */
    private function insertBefore(string $target, string $id, string $class, string $content, array $attrs = [])
    {
        // Insert a div with the id before the target
        $this->response->insertBefore($target, $this->ui->formRowTag(), $id);
        // Set the new element class
        $this->jq("#$id")->attr('class', $this->ui->formRowClass($class));
        // Set the new element attributes
        foreach($attrs as $name => $value)
        {
            $this->jq("#$id")->attr($name, $value);
        }
        // Set the new element content
        $this->response->html($id, $content);
    }

    /**
     * Insert a new HTML element after a given target
     *
     * @param string $target      The target element
     * @param string $id          The new element id
     * @param string $class       The new element class
     * @param string $content     The new element content
     * @param array  $attrs       The new element attributes
     *
     * @return void
     */
    private function insertAfter(string $target, string $id, string $class, string $content, array $attrs = [])
    {
        // Insert a div with the id after the target
        $this->response->insertAfter($target, $this->ui->formRowTag(), $id);
        // Set the new element class
        $this->jq("#$id")->attr('class', $this->ui->formRowClass($class));
        // Set the new element attributes
        foreach($attrs as $name => $value)
        {
            $this->jq("#$id")->attr($name, $value);
        }
        // Set the new element content
        $this->response->html($id, $content);
    }

    /**
     * Insert a new column at a given position
     *
     * @param int    $length      The number of columns in the table
     * @param int    $target      The new column is added before this position. Set to -1 to add at the end.
     *
     * @return Response
     */
    public function add(int $length, int $target = -1): Response
    {
        $tableData = $this->db->getTableData();
        // Make data available to views
        $this->view()->shareValues($tableData);

        $columnClass = "{$this->formId}-column";
        $columnId = sprintf('%s-%02d', $columnClass, $length);
        $field = $this->db->getTableField();
        $prefixFields = sprintf("fields[%d]", $length + 1);
        $content = $this->ui->tableColumn($columnClass, $length, $field, $prefixFields, $tableData['support'],
            $tableData['collations'], $tableData['unsigned'], $tableData['options'], $target < 0);

        if($target < 0)
        {
            // Add the new column at the end of the list
            $this->response->append($this->formId, 'innerHTML', $content);
        }
        else
        {
            // Insert the new column before the given index
            /*
            * The prepend() function is not suitable here because it rewrites the
            * $targetId element, resetting all its event handlers and inputs.
            */
            $targetId = sprintf('%s-%02d', $columnClass, $target);
            $this->insertAfter($targetId, $columnId, $columnClass, $content, ['data-index' => $length]);
            // $this->response->prepend($targetId, 'outerHTML', $content);
        }

        $contentId = $this->package->getDbContentId();
        $length = jq(".$columnClass", "#$contentId")->length;
        $index = jq()->attr('data-index');
        // Set the button event handlers on the new column
        $this->jq('[data-field]', "#$columnId")
            ->on('jaxon.dbadmin.renamed', pm()->js('jaxon.dbadmin.onColumnRenamed'));
        $this->jq('.adminer-table-column-add', "#$columnId")->click($this->rq()->add($length, $index));
        $this->jq('.adminer-table-column-del', "#$columnId")->click($this->rq()->del($length, $index)
            ->confirm('Delete this column?'));

        return $this->response;
    }

    /**
     * Delete a column
     *
     * @param int    $length      The number of columns in the table
     * @param int    $index       The column index
     *
     * @return Response
     */
    public function del(int $length, int $index): Response
    {
        $columnId = sprintf('%s-column-%02d', $this->formId, $index);

        // Delete the column
        $this->response->remove($columnId);

        // Reset the added columns ids and input names, so they remain contiguous.
        $length--;
        for($id = $index; $id < $length; $id++)
        {
            $currId = sprintf('%s-column-%02d', $this->formId, $id + 1);
            $nextId = sprintf('%s-column-%02d', $this->formId, $id);
            $this->jq("#$currId")->attr('data-index', $id)->attr('id', $nextId);
            $this->jq('.adminer-table-column-buttons', "#$nextId")->attr('data-index', $id);
            $this->jq('[data-field]', "#$nextId")->trigger('jaxon.dbadmin.renamed');
        }

        return $this->response;
    }

    /**
     * Mark an existing column as to be deleted
     *
     * @param int    $index       The column index
     *
     * @return Response
     */
    public function setForDelete(int $index): Response
    {
        $columnId = sprintf('%s-column-%02d', $this->formId, $index);

        // To mark a column as to be dropped, set its name to an empty string.
        $this->jq('input.column-name', "#$columnId")->attr('name', '');
        // Replace the icon and the onClick event handler.
        $this->jq("#adminer-table-column-button-group-drop-$index")
            ->removeClass('btn-primary')
            ->addClass('btn-danger');
        $this->jq('.adminer-table-column-del', "#$columnId")
            // Remove the current onClick handler before setting a new one.
            ->unbind('click')->click($this->rq()->cancelDelete($index));
        // $this->jq('.adminer-table-column-del>span', "#$columnId")
        //     ->removeClass('glyphicon-remove')
        //     ->addClass('glyphicon-trash');

        return $this->response;
    }

    /**
     * Cancel delete on an existing column
     *
     * @param int    $index       The column index
     *
     * @return Response
     */
    public function cancelDelete(int $index): Response
    {
        $columnId = sprintf('%s-column-%02d', $this->formId, $index);
        $columnName = sprintf('fields[%d][field]', $index + 1);

        // To cancel the drop, reset the column name to its initial value.
        $this->jq('input.column-name', "#$columnId")->attr('name', $columnName);
        // Replace the icon and the onClick event handler.
        $this->jq("#adminer-table-column-button-group-drop-$index")
            ->removeClass('btn-danger')
            ->addClass('btn-primary');
        $this->jq('.adminer-table-column-del', "#$columnId")
            // Remove the current onClick handler before setting a new one.
            ->unbind('click')->click($this->rq()->setForDelete($index));
        // $this->jq('.adminer-table-column-del>span', "#$columnId")
        //     ->removeClass('glyphicon-trash')
        //     ->addClass('glyphicon-remove');

        return $this->response;
    }
}
