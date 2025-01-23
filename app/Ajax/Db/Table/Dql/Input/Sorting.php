<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Input;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;

use function count;

/**
 * This class provides select query features on tables.
 */
class Sorting extends Component
{
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
            $table = $this->bag('dbadmin')->get('db.table.name');
            $options = $this->bag('dbadmin.select')->get('options');
            $selectData = $this->db->getSelectData($table, $options);
            $options = [
                'columns' => $selectData['options']['sorting']['columns'] ?? [],
            ];
        }

        return  $this->ui->formQuerySorting($values, $options);
    }

    public function show()
    {
        // Render the component with the values from the databag.
        $values = $this->bag('dbadmin.select')->get('sorting', []);

        $this->stash()->set('values', $values);
        $this->render();
    }

    public function add(array $values)
    {
        $orders = $values['order'] ?? [];
        $orders[] = ''; // New value
        $values['order'] = $orders;
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
