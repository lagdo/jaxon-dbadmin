<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use function function_exists;

trait DumpTrait
{
    /**
     * Returns export format options
     *
     * @return array
     */
    public function dumpFormat(): array
    {
        return [
            'sql' => 'SQL',
            // 'csv' => 'CSV,',
            // 'csv;' => 'CSV;',
            // 'tsv' => 'TSV',
        ];
    }

    /**
     * Returns export output options
     *
     * @return array
     */
    public function dumpOutput(): array
    {
        $output = [
            'open' => $this->utils->trans->lang('open'),
            'save' => $this->utils->trans->lang('save'),
        ];
        if (function_exists('gzencode')) {
            $output['gzip'] = 'gzip';
        }
        return $output;
    }

    /**
     * Set the path of the file for webserver load
     *
     * @return string
     */
    public function importServerPath(): string
    {
        return 'adminer.sql';
    }
}
