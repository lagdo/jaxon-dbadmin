<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

class ForeignKeyFormDto
{
    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $table = '';

    /**
     * @var string
     */
    public string $column = '';

    /**
     * @var string
     */
    public string $onUpdate = '';

    /**
     * @var string
     */
    public string $onDelete = '';

    /**
     * @var boolean
     */
    public bool $deferrable = false;

    /**
     * @return string
     */
    public function idInUi(): string
    {
        return "{$this->table}::{$this->column}";
    }
}
