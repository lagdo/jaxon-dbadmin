<?php

namespace Lagdo\DbAdmin\Db\UiData;

use Lagdo\DbAdmin\Db\Driver\AbstractProxy;

use function compact;
use function preg_replace;

class TableExport extends AbstractProxy
{
    public function getSelectOutputValues(): array
    {
        return $this->page()->dumpOutput();
    }

    public function getSelectFormatValues(): array
    {
        return $this->page()->dumpFormat();
    }

    public function getSelectDatabaseValues(): array
    {
        return ['', 'USE', 'DROP+CREATE', 'CREATE'];
    }

    public function getSelectTableValues(): array
    {
        return ['', 'DROP+CREATE', 'CREATE'];
    }

    public function getSelectDataValues(): array
    {
        //! use insertOrUpdate() in all drivers
        return !$this->driver()->sql() ? ['', 'TRUNCATE+INSERT', 'INSERT'] :
            ['', 'TRUNCATE+INSERT', 'INSERT', 'INSERT+UPDATE'];
    }

    private function getDataRowOptions(string $database, string $table): array
    {
        // \parse_str($_COOKIE['adminer_export'], $options);
        // if(!$options) {
        $options = [
            'output' => 'open',
            'format' => 'sql',
            'db_style' => ($database !== '' ? '' : 'CREATE'),
            'table_style' => 'DROP+CREATE',
            'data_style' => 'INSERT',
            'types' => true,
        ];
        // }
        // if(!isset($options['events'])) { // backwards compatibility
        $options['routines'] = $options['events'] = ($table === '');
        $options['triggers'] = true; // $options['table_style']; // Is a boolean
        // }
        return $options;
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @return array
     */
    public function getBaseOptions(string $database, string $table): array
    {
        // From dump.inc.php
        $row = $this->getDataRowOptions($database, $table);
        $options = [
            'output' => [
                'label' => $this->utils()->lang('Output'),
                'options' => $this->getSelectOutputValues(),
                'value' => $row['output'],
            ],
            'format' => [
                'label' => $this->utils()->lang('Format'),
                'options' => $this->getSelectFormatValues(),
                'value' => $row['format'],
            ],
            'table_style' => [
                'label' => $this->utils()->lang('Table'),
                'options' => $this->getSelectTableValues(),
                'value' => $row['table_style'],
            ],
            'auto_increment' => [
                'label' => $this->utils()->lang('Auto Increment'),
                'value' => 1,
                'checked' => $row['autoIncrement'] ?? true,
            ],
            'data_style' => [
                'label' => $this->utils()->lang('Data'),
                'options' => $this->getSelectDataValues(),
                'value' => $row['data_style'],
            ],
        ];
        if ($this->driver()->support('trigger')) {
            $options['triggers'] = [
                'label' => $this->utils()->lang('Triggers'),
                'value' => 1,
                'checked' => $row['triggers'],
            ];
        }
        if ($this->driver()->sqlite()) {
            return $options;
        }

        $options['db_style'] = [
            'label' => $this->utils()->lang('Database'),
            'options' => $this->getSelectDatabaseValues(),
            'value' => $row['db_style'],
        ];
        if ($this->driver()->support('type')) {
            $options['types'] = [
                'label' => $this->utils()->lang('Types'),
                'value' => 1,
                'checked' => $row['types'],
            ];
        }
        if ($this->driver()->support('routine')) {
            $options['routines'] = [
                'label' => $this->utils()->lang('Routines'),
                'value' => 1,
                'checked' => $row['routines'],
            ];
        }
        if ($this->driver()->support('event')) {
            $options['events'] = [
                'label' => $this->utils()->lang('Events'),
                'value' => 1,
                'checked' => $row['events'],
            ];
        }
        return $options;
    }

    /**
     * @return array
     */
    public function getDbTables(): array
    {
        $tables = [
            'headers' => [$this->utils()->lang('Tables'), $this->utils()->lang('Data')],
            'details' => [],
        ];
        $tables_list = $this->driver()->tables();
        foreach ($tables_list as $name => $type) {
            $prefix = preg_replace('~_.*~', '', $name);
            //! % may be part of table name
            // $checked = ($TABLE == '' || $TABLE == (\substr($TABLE, -1) == '%' ? "$prefix%" : $name));
            // $results['prefixes'][$prefix]++;

            $tables['details'][] = compact('prefix', 'name', 'type'/*, 'checked'*/);
        }
        return $tables;
    }

    /**
     * @return array
     */
    public function getDatabases(): array
    {
        $databases = [
            'headers' => [$this->utils()->lang('Database'), $this->utils()->lang('Data')],
            'details' => [],
        ];
        $databases_list = $this->driver()->databases(false) ?? [];
        foreach ($databases_list as $name) {
            if (!$this->driver()->isInformationSchema($name)) {
                $prefix = preg_replace('~_.*~', '', $name);
                // $results['prefixes'][$prefix]++;

                $databases['details'][] = compact('prefix', 'name');
            }
        }
        return $databases;
    }
}
