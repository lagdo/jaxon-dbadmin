<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Db\Driver\AbstractProxy;
use Lagdo\DbAdmin\Support\Dto\TableDto;

class TableHeader extends AbstractProxy
{
    /**
     * @param TableDto $status
     *
     * @return array<string, string>
     */
    private function getTabs(TableDto $status): array
    {
        $tabs = [
            'fields' => $this->utils()->lang('Columns'),
        ];
        if ($this->driver()->isView($status)) {
            if ($this->driver()->support('view_trigger')) {
                $tabs['triggers'] = $this->utils()->lang('Triggers');
            }
            return $tabs;
        }

        if ($this->driver()->support('indexes')) {
            $tabs['indexes'] = $this->utils()->lang('Indexes');
        }
        if ($this->driver()->supportForeignKeys($status)) {
            $tabs['foreign-keys'] = $this->utils()->lang('Foreign keys');
        }
        if ($this->driver()->support('trigger')) {
            $tabs['triggers'] = $this->utils()->lang('Triggers');
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
        $name = $this->page()->tableName($status);

        return [
            'title' => $this->utils()->lang('Table') . ': ' .
                ($name != '' ? $name : $this->utils()->html($table)),
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
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Collation'),
        ];
        if ($this->driver()->support('comment')) {
            $headers[] = $this->utils()->lang('Comment');
        }

        return $headers;
    }

    /**
     * @return array<string>
     */
    public function indexes(): array
    {
        return [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Column'),
        ];
    }

    /**
     * @return array<string>
     */
    public function foreignKeys(): array
    {
        return [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Source'),
            $this->utils()->lang('Target'),
            $this->utils()->lang('ON DELETE'),
            $this->utils()->lang('ON UPDATE'),
        ];
    }

    /**
     * @return array<string>
     */
    public function triggers(): array
    {
        return [
            $this->utils()->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];
    }
}
