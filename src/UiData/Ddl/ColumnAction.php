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

    /**
     * @param string $value
     *
     * @return ColumnAction
     */
    public static function convert(string $value): ColumnAction
    {
        return self::tryFrom($value) ?? self::NONE;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function equalsAdd(string $value): bool
    {
        return (self::tryFrom($value) ?? null) === self::ADD;
    }
}
