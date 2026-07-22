<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Callback;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;

class DeleteFunc extends FuncComponent
{
    /**
     * @param string $columnId
     * @param array<ColumnFormDto> $inputs
     *
     * @return array
     */
    private function updateColumns(string $columnId, array $inputs): array
    {
        $input = $inputs[$columnId];
        if ($input->added()) {
            // Remove the column.
            $inputs[$columnId] = null;
            return $inputs;
        }

        // An existing column is set to be dropped.
        $input->drop();
        return $inputs;
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.bagTableForm')]
    public function exec(string $columnId): void
    {
        $inputs = $this->getColumnInputs();
        if (!isset($inputs[$columnId])) {
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $inputs = $this->updateColumns($columnId, $inputs);
        $this->cl(Wrapper::class)->show($this->metadata(), $inputs);
    }

    /**
     * @param string $columnId
     * @param array<ColumnFormDto> $inputs
     *
     * @return array
     */
    private function undoColumn(string $columnId, array $inputs): array
    {
        // Reset the column. Only the status needs to be updated.
        $inputs[$columnId]->changeIf();
        return $inputs;
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.bagTableForm')]
    public function cancel(string $columnId): void
    {
        $inputs = $this->getColumnInputs();
        if (!isset($inputs[$columnId])) {
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $inputs = $this->undoColumn($columnId, $inputs);
        $this->cl(Wrapper::class)->show($this->metadata(), $inputs);
    }
}
