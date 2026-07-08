<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

class DatabaseHeader extends AbstractDriverProxy
{
    /**
     * @return array<string>
     */
    public function tables(): array
    {
        return [
            'name' => $this->utils()->lang('Table'),
            'engine' => $this->utils()->lang('Engine'),
            'collation' => $this->utils()->lang('Collation'),
            'auto_increment' => $this->utils()->lang('Auto Increment'),
            'data_length' => $this->utils()->lang('Data Length'),
            'index_length' => $this->utils()->lang('Index Length'),
            'data_free' => $this->utils()->lang('Data Free'),
            'row_count' => $this->utils()->lang('Rows'),
        ];
    }

    /**
     * @return array<string>
     */
    public function views(): array
    {
        return [
            'name' => $this->utils()->lang('View'),
            'engine' => $this->utils()->lang('Engine'),
            'data_length' => $this->utils()->lang('Data Length'),
            'index_length' => $this->utils()->lang('Index Length'),
            'row_count' => $this->utils()->lang('Rows'),
        ];
    }

    /**
     * @return array<string>
     */
    public function routines(): array
    {
        return [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Type'),
            $this->utils()->lang('Return type'),
        ];
    }

    /**
     * @return array<string>
     */
    public function sequences(): array
    {
        return [
            $this->utils()->lang('Name'),
        ];
    }

    /**
     * @return array<string>
     */
    public function userTypes(): array
    {
        return [
            $this->utils()->lang('Name'),
        ];
    }

    /**
     * @return array<string>
     */
    public function events(): array
    {
        return [
            $this->utils()->lang('Name'),
            $this->utils()->lang('Schedule'),
            $this->utils()->lang('Start'),
            // $this->utils()->lang('End'),
        ];
    }
}
