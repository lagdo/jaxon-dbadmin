<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Fields\Form;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options\Component;

use function array_filter;

abstract class AbstractForm extends Component
{
    /**
     * @var string
     */
    protected string $fieldId;

    /**
     * @return array
     */
    abstract protected function newEntry(): array;

    /**
     * @return void
     */
    public function show(): void
    {
        // Render the component with the values from the databag.
        $values = $this->getSelectBag($this->fieldId, []);

        $this->stash()->set('values', $values);
        $this->render();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    public function add(array $values): void
    {
        $newEntry = $this->newEntry();
        $newEntry['delete'] = false;
        $values[$this->fieldId] = [...($values[$this->fieldId] ?? []), $newEntry];

        $this->stash()->set('values', $values);
        $this->render();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    public function del(array $values): void
    {
        $delete = fn(array $value) => empty($value['delete']);
        $values[$this->fieldId] = array_filter($values[$this->fieldId], $delete);

        $this->stash()->set('values', $values);
        $this->render();
    }
}
