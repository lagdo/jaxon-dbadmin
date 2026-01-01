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
     * @param array  $values
     * @param int    $position      The new column is moved before this position.
     *
     * @return void
     */
    public function up(array $values, int $position): void
    {
    }

    /**
     * @param array  $values
     * @param int    $position      The new column is moved after this position.
     *
     * @return void
     */
    public function down(array $values, int $position): void
    {
    }
}
