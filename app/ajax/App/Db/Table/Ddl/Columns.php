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
     * @var string
     */
    protected $overrides = '';

    /**
     * The form id
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
    }

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
     *
     * @return void
     */
    private function _render(array $fields): void
    {
        $this->tableData = $this->db()->getTableData($this->getTableName());
        // Make data available to views
        $this->view()->shareValues($this->tableData);
        $this->ui()
            ->support($this->tableData['support'])
            ->collations($this->tableData['collations'])
            ->unsigned($this->tableData['unsigned'])
            ->options($this->tableData['options']);

        $fields = array_values($fields);
        $this->stash()->set('table.fields', $fields);
        $this->bag('dbadmin.table')->set('fields', $fields);
        $this->render();
    }

    /**
     * @param array $formValues
     *
     * @return TableFieldEntity[]
     */
    private function getFields(array $formValues): array
    {
        $fieldsValues = $formValues['fields'] ?? [];
        return array_map(
            fn($field) => TableFieldEntity::fromArray($field)->update($fieldsValues),
            $this->bag('dbadmin.table')->get('fields', [])
        );
    }

    /**
     * Insert a new column at a given position
     *
     * @param array  $formValues
     * @param int    $target      The new column is added before this position. Set to -1 to add at the end.
     *
     * @return void
     */
    public function add(array $formValues, int $target = -1): void
    {
        $fields = $this->getFields($formValues);
        // Append a new empty field entry
        $newField = $this->db()->getTableField();
        $newField->editStatus = 'added';
        $newField->editPosition = count($fields);
        $fields[] = $newField;

        $this->_render($fields);
    }

    /**
     * @param array<TableFieldEntity> $fields
     * @param int $position
     *
     * @return array
     */
    private function deleteColumn(array $fields, int $position): array
    {
        if($fields[$position]->editStatus !== 'added')
        {
            // An existing field is marked as to be deleted.
            $fields[$position]->editStatus = 'deleted';
            return $fields;
        }

        // An added field is removed. The positions must be updated.
        $fields = array_filter($fields, fn($field) => $field->editPosition !== $position);
        $editPosition = 0;
        foreach($fields as $field)
        {
            $field->editPosition = $editPosition++;
        }
        return $fields;
    }

    /**
     * Delete a column
     *
     * @param array  $formValues
     * @param int    $position
     *
     * @return void
     */
    public function del(array $formValues, int $position): void
    {
        $fields = $this->getFields($formValues);
        if(!isset($fields[$position]))
        {
            return;
        }

        // Delete the column
        $fields = $this->deleteColumn($fields, $position);

        $this->_render($fields);
    }

    /**
     * Cancel a delete on an existing column
     *
     * @param array  $formValues
     * @param int    $position
     *
     * @return void
     */
    public function cancel(array $formValues, int $position): void
    {
        $fields = $this->getFields($formValues);
        if(!isset($fields[$position]) || $fields[$position]->editStatus !== 'deleted')
        {
            return;
        }

        // Change the column status
        $fields[$position]->editStatus = 'existing';

        $this->_render($fields);
    }
}
