<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

use Lagdo\DbAdmin\Driver\EngineInterface;

use function preg_match;

trait DescriptionColumnTrait
{
    /**
     * @return EngineInterface
     */
    abstract protected function engine(): EngineInterface;

    /**
     * @param string $table
     *
     * @return string
     */
    public function getDescriptionColumn(string $table): string
    {
        // Take the first varchar or text column.
        foreach ($this->engine()->columns($table) as $column) {
            // if (preg_match("~varchar|character varying~", $column->type)) {
            if (!$column->primary && preg_match("~char|text~", $column->type)) {
                return $column->name;
            }
        }

        return '';
    }
}
