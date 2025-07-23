<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Component;

use function count;

/**
 * This class provides select query features on tables.
 */
class Filters extends Component
{
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
            $options = $this->bag('dbadmin.select')->get('options');
            $selectData = $this->db()->getSelectData($this->getTableName(), $options);
            $options = [
                'columns' => $selectData['options']['filters']['columns'] ?? [],
                'operators' => $selectData['options']['filters']['operators'] ?? [],
            ];
        }

        return  $this->ui()->formQueryFilters($values, $options);
    }

    public function show(): void
    {
        // Render the component with the values from the databag.
        $values = $this->bag('dbadmin.select')->get('filters', []);

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function add(array $values): void
    {
        $wheres = $values['where'] ?? [];
        $wheres[] = ['col' => '', 'op' => '', 'val' => '']; // New value
        $values['where'] = $wheres;
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
