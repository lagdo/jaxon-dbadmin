<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options\Fields\Form;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Component;

use function count;

/**
 * This class provides select query features on tables.
 */
class Columns extends Component
{
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
            $options = $this->bag('dbadmin.select')->get('options');
            $selectData = $this->db()->getSelectData($this->getTableName(), $options);
            $options = [
                'functions' => $selectData->options['columns']['functions'] ?? [],
                'grouping' => $selectData->options['columns']['grouping'] ?? [],
                'columns' => $selectData->options['columns']['columns'] ?? [],
            ];
        }

        return  $this->selectUi->formColumns($values, $options);
    }

    public function show(): void
    {
        // Render the component with the values from the databag.
        $values = $this->bag('dbadmin.select')->get('columns', []);

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function add(array $values): void
    {
        $columns = $values['column'] ?? [];
        $columns[] = ['fun' => '', 'col' => '']; // New value
        $values['column'] = $columns;
        $values['del'] = []; // Do not delete anything.

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function del(array $values): void
    {
        // By default, deleted values are not rendered.
        $this->stash()->set('values', $values);
        $this->render();
    }
}
