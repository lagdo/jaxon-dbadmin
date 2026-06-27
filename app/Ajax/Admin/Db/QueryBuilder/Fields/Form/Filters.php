<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields\Form;

use function count;

/**
 * This class provides select query features on tables.
 */
class Filters extends AbstractForm
{
    /**
     * @var string
     */
    protected string $fieldId = 'filters';

    /**
     * @return array
     */
    protected function newEntry(): array
    {
        return ['column' => '', 'operator' => '', 'operand' => '', 'delete' => false];
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $values = $this->stash()->get('values', []);
        // Filters options
        $options = ['columns' => [], 'operators' => []];
        if(count($values) > 0)
        {
            $select = $this->getSelectQueryParams();
            $options = [
                'columns' => $select->filterableColumns,
                'operators' => $select->operators,
            ];
        }

        return  $this->optionsUi->formFilters($values, $options);
    }
}
