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
            $this->utils()->lang('Table'),
            $this->utils()->lang('Engine'),
            $this->utils()->lang('Collation'),
            $this->utils()->lang('Auto Increment'),
            $this->utils()->lang('Data Length'),
            $this->utils()->lang('Index Length'),
            $this->utils()->lang('Data Free'),
            $this->utils()->lang('Rows'),
        ];
    }

    /**
     * @return array<string>
     */
    public function views(): array
    {
        return [
            $this->utils()->lang('View'),
            $this->utils()->lang('Engine'),
            $this->utils()->lang('Data Length'),
            $this->utils()->lang('Index Length'),
            $this->utils()->lang('Rows'),
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
