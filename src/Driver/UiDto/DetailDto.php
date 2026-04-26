<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class DetailDto
{
    /**
     * @param array $items
     * @param array|null $menus
     */
    public function __construct(public array $items, public array|null $menus = null)
    {}
}
