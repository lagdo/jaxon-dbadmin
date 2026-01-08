<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Before;

/**
 * Move a column up or down.
 */
#[Before('notYetAvailable')]
class MoveFunc extends FuncComponent
{
    /**
     * @param string $columnId
     * @param array  $values
     *
     * @return void
     */
    public function up(string $columnId, array $values): void
    {
    }

    /**
     * @param string $columnId
     * @param array  $values
     *
     * @return void
     */
    public function down(string $columnId, array $values): void
    {
    }
}
