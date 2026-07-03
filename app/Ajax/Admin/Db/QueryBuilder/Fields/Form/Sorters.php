<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields\Form;

use function count;

/**
 * This class provides select query features on tables.
 */
class Sorters extends AbstractForm
{
    /**
     * @var string
     */
    protected string $fieldId = 'sorters';

    /**
     * @return array
     */
    protected function newEntry(): array
    {
        return ['column' => '', 'desc' => false, 'delete' => false];
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $values = $this->get('values', []);
        // Sorting options
        $options = ['columns' => []];
        if(count($values) > 0)
        {
            $select = $this->getSelectQueryParams();
            $options = [
                'columns' => $select->sortableColumns,
            ];
        }

        return $this->optionsUi->formSorters($values, $options);
    }
}
