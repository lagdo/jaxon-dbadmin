<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DdInputDto;

use function array_filter;
use function array_map;

/**
 * When creating or modifying a table, this component displays its columns.
 * It does not persist data. It keeps data in the databag and only updates the UI.
 */
#[Exclude]
class Wrapper extends Component
{
    /**
     * @var array<DdInputDto>
     */
    private $inputs;

    /**
     * @param array<DdInputDto> $inputs
     *
     * @return void
     */
    private function setColumns(array $inputs): void
    {
        $this->inputs = [];

        // The primary column.
        $primaryColumn = null;
        // Set the columns positions.
        $position = 0;
        foreach ($inputs as $input) {
            $input->position = $position++;
            $this->inputs["column_$position"] = $input;
            if ($input->values()->primary) {
                $primaryColumn = $input->values()->name;
            }
        }

        // Save the columns and the primary column name in the databag.
        $callback = fn($input) => $input->toArray();
        $this->setTableBag('columns', array_map($callback, $this->inputs));
        $this->setTableBag('primary', $primaryColumn);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->tableUi
            ->metadata($this->get('metadata'))
            ->inputs($this->inputs)
            ->showColumns();
    }

    /**
     * @param DdInputDto|null $input
     *
     * @return bool
     */
    private function inputIsValid(DdInputDto|null $input): bool
    {
        if ($input === null) {
            return false;
        }

        $metadata = $this->get('metadata');
        // Null values and columns not found in the database are discarded.
        return $input->added() || isset($metadata['columns'][$input->column->name]);
    }

    /**
     * @param array $metadata
     * @param array<DdInputDto> $inputs
     *
     * @return void
     */
    public function show(array $metadata, array $inputs = []): void
    {
        $this->set('metadata', $metadata);
        $this->setColumns(array_filter($inputs, $this->inputIsValid(...)));

        $this->render();
    }

    /**
     * @param array $metadata
     *
     * @return void
     */
    public function load(array $metadata): void
    {
        $this->set('metadata', $metadata);
        $this->setColumns($metadata['columns']);

        $this->render();
    }
}
