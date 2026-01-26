<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options\Fields\Form;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options\Component;

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
            $options = $this->getSelectBag('options');
            $selectData = $this->db()->getSelectData($this->getCurrentTable(), $options);
            $options = [
                'columns' => $selectData->options['filters']['columns'] ?? [],
                'operators' => $selectData->options['filters']['operators'] ?? [],
            ];
        }

        return  $this->optionsUi->formFilters($values, $options);
    }

    public function show(): void
    {
        // Render the component with the values from the databag.
        $values = $this->getSelectBag('filters', []);

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
