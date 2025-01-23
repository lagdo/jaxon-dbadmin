<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Input;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;

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
            $table = $this->bag('dbadmin')->get('db.table.name');
            $options = $this->bag('dbadmin.select')->get('options');
            $selectData = $this->db->getSelectData($table, $options);
            $options = [
                'functions' => $selectData['options']['columns']['functions'] ?? [],
                'grouping' => $selectData['options']['columns']['grouping'] ?? [],
                'columns' => $selectData['options']['columns']['columns'] ?? [],
            ];
        }

        return  $this->ui->formQueryColumns($values, $options);
    }

    public function show()
    {
        // Render the component with the values from the databag.
        $values = $this->bag('dbadmin.select')->get('columns', []);

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function add(array $values)
    {
        $columns = $values['column'] ?? [];
        $columns[] = ['fun' => '', 'col' => '']; // New value
        $values['column'] = $columns;
        $values['del'] = []; // Do not delete anything.

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function del(array $values)
    {
        // By default, deleted values are not rendered.
        $this->stash()->set('values', $values);
        $this->render();
    }
}
