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
     * @param array $metadata
     *
     * @return TableFormDto
     */
    private function getTableFormDto(array $metadata): TableFormDto
    {
        return $metadata['table'];
    }

    /**
     * @param array $metadata
     *
     * @return void
     */
    private function setMetadata(array $metadata): void
    {
        $table = $this->getTableFormDto($metadata);

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

        $this->set('metadata', $metadata);
        $this->setTableBag('columns', array_map($callback, $table->columns));
    }

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
     * @param array $metadata
     *
     * @return void
     */
    public function load(array $metadata): void
    {
        $this->setMetadata($metadata);

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
     * @param array $metadata
     * @param array<ColumnFormDto> $inputs
     *
     * @return void
     */
    public function show(array $metadata, array $inputs): void
    {
        $table = $this->getTableFormDto($metadata);
        $columns = $table->status->columns();
        $table->columns = array_filter($inputs,
            fn(ColumnFormDto|null $input) => $this->inputIsValid($input, $columns));
        $this->load($metadata);
    }
}
