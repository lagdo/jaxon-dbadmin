<?php

namespace Lagdo\DbAdmin\Db\Driver;

/**
 * Breadcrumbs
 */
class Breadcrumbs
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * Get the breadcrumbs items
     *
     * @return array
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Clear the breadcrumbs
     *
     * @return self
     */
    public function clear(): self
    {
        $this->items = [];
        return $this;
    }

    /**
     * Add an item to the breadcrumbs
     *
     * @param string $label
     *
     * @return self
     */
    public function item(string $label): self
    {
        $this->items[] = $label;
        return $this;
    }
}
