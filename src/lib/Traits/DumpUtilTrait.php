<?php

namespace Lagdo\DbAdmin\Traits;

use function function_exists;

trait DumpUtilTrait
{
    /**
     * Returns export format options
     *
     * @return array
     */
    public function dumpFormat(): array
    {
        return ['sql' => 'SQL', 'csv' => 'CSV,', 'csv;' => 'CSV;', 'tsv' => 'TSV'];
    }

    /**
     * Returns export output options
     *
     * @return array
     */
    public function dumpOutput(): array
    {
        $output = ['text' => $this->trans->lang('open'), 'file' => $this->trans->lang('save')];
        if (function_exists('gzencode')) {
            $output['gz'] = 'gzip';
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
