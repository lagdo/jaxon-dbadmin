<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

trait SelectBagTrait
{
    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function setSelectBag(string $key, $value): void
    {
        $this->setBag('dbadmin.select', $key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getSelectBag(string $key, $value = null): mixed
    {
        return $this->getBag('dbadmin.select', $key, $value);
    }
}
