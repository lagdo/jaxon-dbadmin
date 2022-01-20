<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Ui\Builder\AbstractBuilder;

trait DatabaseTrait
{
    /**
     * @param string $formId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function viewForm(string $formId, bool $materializedView, array $view = []): string
    {
        $this->htmlBuilder->clear()
            ->form(false, true)->setId($formId)
                ->formRow()
                    ->formLabel()->setFor('name')->addText('Name')
                    ->end()
                ->end()
                ->formRow()
                    ->formInput()->setType('text')->setName('name')->setPlaceholder('Name')
                        ->setValue($view['name'] ?? '')
                    ->end()
                ->end()
                ->formRow()
                    ->formLabel()->setFor('select')->addText('SQL query')
                    ->end()
                ->end()
                ->formRow()
                    ->formTextarea()->setRows('10')->setName('select')->setSpellcheck('false')->setWrap('on')
                        ->addText($view['select'] ?? '')
                    ->end()
                ->end();
        if ($materializedView) {
            $this->htmlBuilder
                ->formRow()
                    ->formLabel()->setFor('materialized')->addText('Materialized')
                    ->end()
                ->end()
                ->formRow()
                    ->checkbox($view['materialized'] ?? false)->setName('materialized')
                    ->end()
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return string
     */
    public function importPage(array $htmlIds, array $contents, array $labels): string
    {
        $this->htmlBuilder->clear()
            ->col(12)->setId('adminer-command-details')
            ->end()
            ->col(12)
                ->form(true, false)->setId($htmlIds['formId'])
                    ->row()
                        ->col(6)
                            ->formRow()
                                ->formCol(4)
                                    ->label($labels['file_upload'])
                                    ->end()
                                ->end();
        if (isset($contents['upload'])) {
            $this->htmlBuilder
                                ->formCol(8)->addHtml($contents['upload'])
                                ->end();
        } else {
            $this->htmlBuilder
                                ->formCol(8)->addHtml($contents['upload_disabled'])
                                ->end();
        }
        $this->htmlBuilder
                            ->end()
                            ->formRow();
        if (isset($contents['upload'])) {
            $this->htmlBuilder
                                ->formCol(12)
                                    ->inputGroup()->setId($htmlIds['sqlFilesDivId'])
                                        ->button(AbstractBuilder::BTN_PRIMARY)->setId($htmlIds['sqlChooseBtnId'])
                                            ->addHtml($labels['select'] . '&hellip;')
                                        ->end()
                                        ->input()->setType('file')->setName('sql_files[]')->setId($htmlIds['sqlFilesInputId'])
                                            ->setMultiple('multiple')->setStyle('display:none;')
                                        ->end()
                                        ->formInput()->setType('text')->setReadonly('readonly')
                                        ->end()
                                    ->end()
                                ->end();
        }
        $this->htmlBuilder
                            ->end()
                            ->formRow()
                                ->formCol(4)
                                    ->button(AbstractBuilder::BTN_PRIMARY + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($htmlIds['sqlFilesBtnId'])->addText($labels['execute'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
        if (isset($contents['path'])) {
            $this->htmlBuilder
                        ->col(6)
                            ->formRow()
                                ->formCol(4)
                                    ->label($labels['from_server'])
                                    ->end()
                                ->end()
                                ->formCol(8)->addText($labels['path'])
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(12)
                                    ->formInput()->setType('text')->setValue($contents['path'])->setReadonly('readonly')
                                    ->end()
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(4)
                                    ->button(AbstractBuilder::BTN_PRIMARY + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($htmlIds['webFileBtnId'])->addText($labels['run_file'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                    ->row()
                        ->col(12)
                            ->formRow()
                                ->formCol(3)->addHtml('&nbsp;') // Actually an offset. TODO: a parameter for that.
                                ->end()
                                ->formCol(3)
                                    ->inputGroup()
                                        ->text()->addText($labels['error_stops'])
                                        ->end()
                                        ->checkbox()->setName('error_stops')
                                        ->end()
                                    ->end()
                                ->end()
                                ->formCol(3)
                                    ->inputGroup()
                                        ->text()->addText($labels['only_errors'])
                                        ->end()
                                        ->checkbox()->setName('only_errors')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)->setId('adminer-command-history')
            ->end()
            ->col(12)->setId('adminer-command-results')
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $htmlIds
     * @param array $databases
     * @param array $tables
     * @param array $options
     * @param array $labels
     *
     * @return void
     */
    public function exportPage(array $htmlIds, array $databases, array $tables, array $options, array $labels)
    {
        $this->htmlBuilder->clear()
            ->col(12)
                ->form(true, false)->setId($htmlIds['formId'])
                    ->row()
                        ->col(7)
                            ->formRow()
                                ->formCol(3)
                                    ->label($options['output']['label'])->setFor('output')
                                    ->end()
                                ->end()
                                ->formCol(8);
        foreach ($options['output']['options'] as $value => $label) {
            $this->htmlBuilder
                                    ->radio($options['output']['value'] === $value)->setName('output')
                                    ->end()
                                    ->addHtml('&nbsp;' . $label . '&nbsp;');
        }
        $this->htmlBuilder
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(3)
                                    ->label($options['format']['label'])->setFor('format')
                                    ->end()
                                ->end()
                                ->formCol(8);
        foreach ($options['format']['options'] as $value => $label) {
            $this->htmlBuilder
                                    ->radio($options['format']['value'] === $value)->setName('format')
                                    ->end()
                                    ->addHtml('&nbsp;' . $label . '&nbsp;');
        }
        $this->htmlBuilder
                                ->end()
                            ->end();
        if (isset($options['db_style'])) {
            $this->htmlBuilder
                            ->formRow()
                                ->formCol(3)
                                    ->label($options['db_style']['label'])->setFor('db_style')
                                    ->end()
                                ->end()
                                ->formCol(8)
                                    ->formSelect()->setName('db_style');
            foreach ($options['db_style']['options'] as $label) {
                $this->htmlBuilder
                                        ->option($options['db_style']['value'] == $label, $label)
                                        ->end();
            }
            $this->htmlBuilder
                                    ->end()
                                ->end()
                            ->end();
        }
        if (isset($options['routines']) || isset($options['events'])) {
            $this->htmlBuilder
                            ->formRow()
                                ->formCol(3)->addHtml('&nbsp;') // Actually an offset. TODO: a parameter for that.
                                ->end();
            if (isset($options['routines'])) {
                $this->htmlBuilder
                                ->formCol(4)
                                    ->checkbox($options['routines']['checked'])->setName('routines')
                                        ->setValue($options['routines']['value'])
                                    ->end()
                                    ->addHtml('&nbsp;' . $options['routines']['label'])
                                ->end();
            }
            if (isset($options['events'])) {
                $this->htmlBuilder
                                ->formCol(4)
                                    ->checkbox($options['events']['checked'])->setName('events')
                                        ->setValue($options['events']['value'])
                                    ->end()
                                    ->addHtml('&nbsp;' . $options['events']['label'])
                                ->end();
            }
            $this->htmlBuilder
                            ->end();
        }
        $this->htmlBuilder
                            ->formRow()
                                ->formCol(3)
                                    ->label($options['table_style']['label'])->setFor('table_style')
                                    ->end()
                                ->end()
                                ->formCol(8)
                                    ->formSelect()->setName('table_style');
        foreach ($options['table_style']['options'] as $label) {
            $this->htmlBuilder
                                        ->option($options['table_style']['value'] == $label, $label)
                                        ->end();
        }
        $this->htmlBuilder
                                    ->end()
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(3)->addHtml('&nbsp;') // Actually an offset. TODO: a parameter for that.
                                ->end()
                                ->formCol(4)
                                    ->checkbox($options['auto_increment']['checked'])->setName('auto_increment')
                                        ->setValue($options['auto_increment']['value'])
                                    ->end()
                                    ->addHtml('&nbsp;' . $options['auto_increment']['label'])
                                ->end();
        if (isset($options['triggers'])) {
            $this->htmlBuilder
                                ->formCol(4)
                                    ->checkbox($options['triggers']['checked'])->setName('triggers')
                                        ->setValue($options['triggers']['value'])
                                    ->end()
                                    ->addHtml('&nbsp;' . $options['triggers']['label'])
                                ->end();
        }
        $this->htmlBuilder
                            ->end()
                            ->formRow()
                                ->formCol(3)
                                    ->label($options['data_style']['label'])->setFor('data_style')
                                    ->end()
                                ->end()
                                ->formCol(8)
                                    ->formSelect()->setName('data_style');
        foreach ($options['data_style']['options'] as $label) {
            $this->htmlBuilder
                                        ->option($options['data_style']['value'] == $label, $label)
                                        ->end();
        }
        $this->htmlBuilder
                                    ->end()
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(3)->addHtml('&nbsp;') // Actually an offset. TODO: a parameter for that.
                                ->end()
                                ->formCol(4)
                                    ->button(AbstractBuilder::BTN_PRIMARY + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($htmlIds['btnId'])->addText($labels['export'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->col(5);
        if (($databases)) {
            $this->htmlBuilder
                            ->table(true, 'bordered')
                                ->thead()
                                    ->tr()
                                        ->th()
                                            ->checkbox(true)->setId($htmlIds['databaseNameId'] . '-all')
                                            ->end()
                                            ->addHtml('&nbsp;' . $databases['headers'][0])
                                        ->end()
                                        ->th()
                                            ->checkbox(true)->setId($htmlIds['databaseDataId'] . '-all')
                                            ->end()
                                            ->addHtml('&nbsp;' . $databases['headers'][1])
                                        ->end()
                                    ->end()
                                ->end()
                                ->tbody();
            foreach ($databases['details'] as $database) {
                $this->htmlBuilder
                                    ->tr()
                                        ->td()
                                            ->checkbox(true)->setName('database_list[]')
                                                ->setClass($htmlIds['databaseNameId'])->setValue($database['name'])
                                            ->end()
                                            ->addHtml('&nbsp;' . $database['name'])
                                        ->end()
                                        ->td()
                                            ->checkbox(true)->setName('database_data[]')
                                                ->setClass($htmlIds['databaseDataId'])->setValue($database['name'])
                                            ->end()
                                        ->end()
                                    ->end();
            }
            $this->htmlBuilder
                                ->end()
                            ->end();
        }
        if (($tables)) {
            $this->htmlBuilder
                            ->table(true, 'bordered')
                                ->thead()
                                    ->tr()
                                        ->th()
                                            ->checkbox(true)->setId($htmlIds['tableNameId'] . '-all')
                                            ->end()
                                            ->addHtml('&nbsp;' . $tables['headers'][0])
                                        ->end()
                                        ->th()
                                            ->checkbox(true)->setId($htmlIds['tableDataId'] . '-all')
                                            ->end()
                                            ->addHtml('&nbsp;' . $tables['headers'][1])
                                        ->end()
                                    ->end()
                                ->end()
                                ->tbody();
            foreach ($tables['details'] as $table) {
                $this->htmlBuilder
                                    ->tr()
                                        ->td()
                                            ->checkbox(true)->setName('table_list[]')
                                                ->setClass($htmlIds['tableNameId'])->setValue($table['name'])
                                            ->end()
                                            ->addHtml('&nbsp;' . $table['name'])
                                        ->end()
                                        ->td()
                                            ->checkbox(true)->setName('table_data[]')
                                                ->setClass($htmlIds['tableDataId'])->setValue($table['name'])
                                            ->end()
                                        ->end()
                                    ->end();
            }
            $this->htmlBuilder
                                ->end()
                            ->end();
        }
        $this->htmlBuilder
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)->setId('adminer-export-results')
            ->end();
        return $this->htmlBuilder->build();
    }
}
