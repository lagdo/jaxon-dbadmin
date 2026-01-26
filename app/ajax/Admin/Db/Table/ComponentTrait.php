<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

trait ComponentTrait
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $this->db()->selectDatabase($server, $database, $schema);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function setTableBag(string $key, $value): void
    {
        $this->setBag('dbadmin.table', $key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getTableBag(string $key, $value = null): mixed
    {
        return $this->getBag('dbadmin.table', $key, $value);
    }

    /**
     * Set the current table name
     *
     * @param string $table
     *
     * @return void
     */
    protected function setCurrentTable(string $table): void
    {
        $this->setTableBag('current', $table);
    }

    /**
     * Get the current table name
     *
     * @return string
     */
    protected function getCurrentTable(): string
    {
        return $this->getTableBag('current', '');
    }
}
