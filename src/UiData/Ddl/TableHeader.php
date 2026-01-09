<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

class TableHeader
{
    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private AppPage $page,
        private DriverInterface $driver, private Utils $utils)
    {}

    /**
     * @param TableDto $status
     *
     * @return array<string, string>
     */
    private function getTabs(TableDto $status): array
    {
        $tabs = [
            'fields' => $this->utils->trans->lang('Columns'),
            // 'indexes' => $this->utils->trans->lang('Indexes'),
            // 'foreign-keys' => $this->utils->trans->lang('Foreign keys'),
            // 'triggers' => $this->utils->trans->lang('Triggers'),
        ];
        if ($this->driver->isView($status)) {
            if ($this->driver->support('view_trigger')) {
                $tabs['triggers'] = $this->utils->trans->lang('Triggers');
            }
            return $tabs;
        }

        if ($this->driver->support('indexes')) {
            $tabs['indexes'] = $this->utils->trans->lang('Indexes');
        }
        if ($this->driver->supportForeignKeys($status)) {
            $tabs['foreign-keys'] = $this->utils->trans->lang('Foreign keys');
        }
        if ($this->driver->support('trigger')) {
            $tabs['triggers'] = $this->utils->trans->lang('Triggers');
        }
        return $tabs;
    }

    /**
     * @param string $table
     * @param TableDto $status
     *
     * @return array
     */
    public function infos(string $table, TableDto $status): array
    {
        $name = $this->page->tableName($status);

        return [
            'title' => $this->utils->trans->lang('Table') . ': ' .
                ($name != '' ? $name : $this->utils->str->html($table)),
            'comment' => $status->comment,
            'tabs' => $this->getTabs($status),
        ];
    }

    /**
     * @return array<string>
     */
    public function fields(): array
    {
        $headers = [
            $this->utils->trans->lang('Name'),
            $this->utils->trans->lang('Type'),
            $this->utils->trans->lang('Collation'),
        ];
        if ($this->driver->support('comment')) {
            $headers[] = $this->utils->trans->lang('Comment');
        }

        return $headers;
    }

    /**
     * @return array<string>
     */
    public function indexes(): array
    {
        return [
            $this->utils->trans->lang('Name'),
            $this->utils->trans->lang('Type'),
            $this->utils->trans->lang('Column'),
        ];
    }

    /**
     * @return array<string>
     */
    public function foreignKeys(): array
    {
        return [
            $this->utils->trans->lang('Name'),
            $this->utils->trans->lang('Source'),
            $this->utils->trans->lang('Target'),
            $this->utils->trans->lang('ON DELETE'),
            $this->utils->trans->lang('ON UPDATE'),
        ];
    }

    /**
     * @return array<string>
     */
    public function triggers(): array
    {
        return [
            $this->utils->trans->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];
    }
}
