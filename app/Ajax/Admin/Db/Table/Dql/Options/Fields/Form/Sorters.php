<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Fields\Form;

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
        return ['desc' => false, 'column' => '', 'delete' => false];
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $values = $this->stash()->get('values', []);
        // Sorting options
        $options = ['columns' => []];
        if(count($values) > 0)
        {
            $options = $this->getSelectBag('options');
            $select = $this->db()->getSelectParams($this->getCurrentTable(), $options);
            $options = [
                'columns' => $select->sortableColumns,
            ];
        }

        return $this->optionsUi->formSorters($values, $options);
    }
}
