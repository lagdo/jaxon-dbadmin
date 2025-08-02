<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Component;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_filter;
use function array_map;
use function array_values;
use function count;

/**
 * When creating or modifying a table, this class
 * provides CRUD features on table columns.
 * It does not persist data. It only updates the UI.
 *
 * @databag dbadmin.table
 */
class Columns extends Component
{
    /**
     * @var array
     */
    private $tableData;

    /**
     * The form id
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()
            ->fields($this->stash()->get('table.fields'))
            ->formId($this->formId)
            ->tableColumns();
    }

    /**
     * @param array $fields
     * @param array  $values
     *
     * @return void
     */
    private function renderFields(array $fields, array $values): void
    {
        // The items in the fields array are not immutable.
        // We must convert them to array saving in the databag, because they are modified later.
        $this->bag('dbadmin.table')->set('fields',
            array_map(fn(TableFieldEntity $field) => $field->toArray(), $fields));

        $this->tableData = $this->db()->getTableData($this->getTableName());
        // Make data available to views
        $this->ui()
            ->support($this->tableData['support'])
            ->collations($this->tableData['collations'])
            ->unsigned($this->tableData['unsigned'])
            ->options($this->tableData['options']);

        // Update the fields with the values in the form.
        $values = $values['fields'] ?? [];
        foreach ($fields as $field) {
            $field->update($values[$field->editPosition] ?? []);
            $field->types = $this->db()->getFieldTypes($field->type);
        }
        $this->stash()->set('table.fields', $fields);

        $this->render();
    }

    /**
     * @return TableFieldEntity[]
     */
    private function getFields(): array
    {
        $bagValues = $this->bag('dbadmin.table')->get('fields', []);
        $callback = fn($field) => TableFieldEntity::fromArray($field);
        return array_values(array_map($callback, $bagValues));
    }

    /**
     * Insert a new column at a given position
     *
     * @param array  $values
     * @param int    $target      The new column is added before this position. Set to -1 to add at the end.
     *
     * @return void
     */
    public function add(array $values, int $target = -1): void
    {
        $fields = $this->getFields();
        // Append a new empty field entry
        $newField = $this->db()->getTableField();
        $newField->editStatus = 'added';
        $newField->editPosition = count($fields);
        $fields[] = $newField;

        $this->renderFields($fields, $values);
    }

    /**
     * @param array<TableFieldEntity> $fields
     * @param int $position
     *
     * @return array
     */
    private function deleteColumn(array $fields, int $position): array
    {
        if ($fields[$position]->editStatus !== 'added') {
            // An existing field is marked as to be deleted.
            $fields[$position]->editStatus = 'deleted';
            return $fields;
        }

        // An added field is removed. The positions must be updated.
        $fields = array_filter($fields, fn($field) => $field->editPosition !== $position);
        $editPosition = 0;
        foreach ($fields as $field) {
            $field->editPosition = $editPosition++;
        }
        return $fields;
    }

    /**
     * Delete a column
     *
     * @param array  $values
     * @param int    $position
     *
     * @return void
     */
    public function del(array $values, int $position): void
    {
        $fields = $this->getFields();
        if (!isset($fields[$position])) {
            return;
        }

        // Delete the column
        $fields = $this->deleteColumn($fields, $position);

        $this->renderFields($fields, $values);
    }

    /**
     * Cancel a delete on an existing column
     *
     * @param array  $values
     * @param int    $position
     *
     * @return void
     */
    public function cancel(array $values, int $position): void
    {
        $fields = $this->getFields();
        if (!isset($fields[$position]) || $fields[$position]->editStatus !== 'deleted') {
            return;
        }

        // Change the column status
        $fields[$position]->updateStatus($values['fields'][$position] ?? []);

        $this->renderFields($fields, $values);
    }
}
