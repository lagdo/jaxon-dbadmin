<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Fields\Form;

use function count;

/**
 * This class provides select query features on tables.
 */
class Columns extends AbstractForm
{
    /**
     * @var string
     */
    protected string $fieldId = 'columns';

    /**
     * @return array
     */
    protected function newEntry(): array
    {
        return ['func' => '', 'column' => '', 'delete' => false];
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $values = $this->stash()->get('values', []);
        // Columns options
        $options = ['functions' => [], 'grouping' => [], 'columns' => []];
        if(count($values) > 0)
        {
            $options = $this->getSelectBag('options');
            $select = $this->db()->getSelectParams($this->getCurrentTable(), $options);
            $options = [
                'functions' => $select->functions,
                'grouping' => $select->grouping,
                'columns' => $select->selectableColumns,
            ];
        }

        return $this->optionsUi->formColumns($values, $options);
    }
}
