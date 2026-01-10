<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

enum ColumnAction: string
{
    case NONE = 'none';

    case ADD = 'add';

    case CHANGE = 'change';

    case DROP = 'drop';
}
