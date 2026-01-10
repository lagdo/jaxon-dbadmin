<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

/**
 * Actions on a table column for create or alter operations.
 */
enum ColumnAction: string
{
    case NONE = 'none';
    case ADD = 'add';
    case CHANGE = 'change';
    case DROP = 'drop';
}
