<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Before;

/**
 * Move a column up or down.
 */
#[Before('notYetAvailable')]
class MoveFunc extends FuncComponent
{
    /**
     * @param string $columnId
     *
     * @return void
     */
    public function up(string $columnId): void
    {
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    public function down(string $columnId): void
    {
    }
}
