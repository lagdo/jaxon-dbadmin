<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;

use function array_combine;
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
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->tableUi
            ->metadata($this->get('metadata'))
            ->setAutoIncrementChecker($this->driver()->typeIsAutoIncrementable(...))
            ->showColumns();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Header::class)->render();
    }

    /**
     * @param TableFormDto $table
     *
     * @return void
     */
    public function load(TableFormDto $table): void
    {
        // Set the columns positions.
        $positions = [];
        $position = 0;
        foreach ($table->columns as $column) {
            $positions[] = "column_$position";
            $column->position = $position++;
        }

        $table->columns = array_combine($positions, $table->columns);
        // Save the columns and the primary column name in the databag.
        $callback = fn($input) => $input->toArray();

        $this->set('metadata', $table);
        $this->setTableBag('columns', array_map($callback, $table->columns));

        $this->render();
    }

    /**
     * @param ColumnFormDto|null $input
     * @param array<ColumnFormDto> $columns
     *
     * @return bool
     */
    private function inputIsValid(ColumnFormDto|null $input, array $columns): bool
    {
        return $input === null ? false :
            // Null values and columns not found in the database are discarded.
            $input->added() || isset($columns[$input->column->name]);
    }

    /**
     * @param TableFormDto $table
     * @param array<ColumnFormDto> $inputs
     *
     * @return void
     */
    public function show(TableFormDto$table, array $inputs): void
    {
        $columns = $table->status->columns();
        $table->columns = array_filter($inputs,
            fn(ColumnFormDto|null $input) => $this->inputIsValid($input, $columns));
        $this->load($table);
    }
}
