<?php

namespace Lagdo\DbAdmin\Db\Facades\Traits;

use function compact;
use function preg_replace;

trait TableExportTrait
{
    private function getDataRowOptions(string $database, string $table): array
    {
        // \parse_str($_COOKIE['adminer_export'], $row);
        // if(!$row) {
        $row = [
            'output' => 'text',
            'format' => 'sql',
            'db_style' => ($database != '' ? '' : 'CREATE'),
            'table_style' => 'DROP+CREATE',
            'data_style' => 'INSERT',
        ];
        // }
        // if(!isset($row['events'])) { // backwards compatibility
        $row['routines'] = $row['events'] = ($table == '');
        $row['triggers'] = $row['table_style'];
        // }
        return $row;
    }

    /**
     * @param string $database
     * @param string $table
     *
     * @return array
     */
    private function getBaseOptions(string $database, string $table): array
    {
        // From dump.inc.php
        $db_style = ['', 'USE', 'DROP+CREATE', 'CREATE'];
        $table_style = ['', 'DROP+CREATE', 'CREATE'];
        $data_style = ['', 'TRUNCATE+INSERT', 'INSERT'];
        if ($this->driver->jush() == 'sql') { //! use insertOrUpdate() in all drivers
            $data_style[] = 'INSERT+UPDATE';
        }

        $row = $this->getDataRowOptions($database, $table);
        $options = [
            'output' => [
                'label' => $this->utils->trans->lang('Output'),
                'options' => $this->admin->dumpOutput(),
                'value' => $row['output'],
            ],
            'format' => [
                'label' => $this->utils->trans->lang('Format'),
                'options' => $this->admin->dumpFormat(),
                'value' => $row['format'],
            ],
            'table_style' => [
                'label' => $this->utils->trans->lang('Tables'),
                'options' => $table_style,
                'value' => $row['table_style'],
            ],
            'auto_increment' => [
                'label' => $this->utils->trans->lang('Auto Increment'),
                'value' => 1,
                'checked' => $row['autoIncrement'] ?? false,
            ],
            'data_style' => [
                'label' => $this->utils->trans->lang('Data'),
                'options' => $data_style,
                'value' => $row['data_style'],
            ],
        ];
        if ($this->driver->jush() !== 'sqlite') {
            $options['db_style'] = [
                'label' => $this->utils->trans->lang('Database'),
                'options' => $db_style,
                'value' => $row['db_style'],
            ];
            if ($this->driver->support('routine')) {
                $options['routines'] = [
                    'label' => $this->utils->trans->lang('Routines'),
                    'value' => 1,
                    'checked' => $row['routines'],
                ];
            }
            if ($this->driver->support('event')) {
                $options['events'] = [
                    'label' => $this->utils->trans->lang('Events'),
                    'value' => 1,
                    'checked' => $row['events'],
                ];
            }
        }
        if ($this->driver->support('trigger')) {
            $options['triggers'] = [
                'label' => $this->utils->trans->lang('Triggers'),
                'value' => 1,
                'checked' => $row['triggers'],
            ];
        }
        return $options;
    }

    /**
     * @return array
     */
    private function getDbTables(): array
    {
        $tables = [
            'headers' => [$this->utils->trans->lang('Tables'), $this->utils->trans->lang('Data')],
            'details' => [],
        ];
        $tables_list = $this->driver->tables();
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
    private function getDatabases(): array
    {
        $databases = [
            'headers' => [$this->utils->trans->lang('Database'), $this->utils->trans->lang('Data')],
            'details' => [],
        ];
        $databases_list = $this->driver->databases(false) ?? [];
        foreach ($databases_list as $name) {
            if (!$this->driver->isInformationSchema($name)) {
                $prefix = preg_replace('~_.*~', '', $name);
                // $results['prefixes'][$prefix]++;

                $databases['details'][] = compact('prefix', 'name');
            }
        }
        return $databases;
    }
}
