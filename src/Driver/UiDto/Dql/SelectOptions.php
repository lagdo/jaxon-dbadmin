<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function intval;

class SelectOptions extends AbstractDriverProxy
{
    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    public function setDefaultOptions(SelectDqDto $input): void
    {
        $defaultOptions = [
            'columns' => [],
            'where' => [],
            'order' => [],
            'desc' => [],
            'fulltext' => [],
            'limit' => '50',
            'length' => '100',
            'page' => '1',
        ];
        foreach ($defaultOptions as $name => $value) {
            $this->utils()->input->values[$name] ??= $value;
            $input->params[$name] ??= $value;
        }
        $page = intval($input->params['page']);
        if ($page > 0) {
            $page -= 1; // Page numbers start at 0 here, instead of 1.
        }
        $input->params['page'] = $page;
        $input->page = $page;
    }

    /**
     * Print columns box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    private function getColumnsOptions(SelectDqDto $input): array
    {
        return [
            'select' => $input->columns,
            'values' => (array)$input->params['columns'],
            'columns' => $input->columnNames,
            'functions' => $this->engine()->functions(),
            'grouping' => $this->engine()->grouping(),
        ];
    }

    /**
     * Print search box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    private function getFiltersOptions(SelectDqDto $input): array
    {
        $fulltexts = [];
        foreach ($input->table->indexes as $i => $index) {
            $fulltexts[$i] = $index->type === 'FULLTEXT' ?
                $this->utils()->html($input->params['fulltext'][$i] ?? '') : '';
        }
        return [
            // 'where' => $where,
            'values' => (array)$input->params['where'],
            'columns' => $input->columnNames,
            'indexes' => $input->table->indexes,
            'operators' => $this->engine()->operators(),
            'fulltexts' => $fulltexts,
        ];
    }

    /**
     * Print order box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    private function getSortingOptions(SelectDqDto $input): array
    {
        $values = [];
        $descs = (array)$input->params['desc'];
        foreach ((array)$input->params['order'] as $key => $value) {
            $values[] = [
                'col' => $value,
                'desc' => $descs[$key] ?? 0,
            ];
        }
        return [
            // 'order' => $order,
            'values' => $values,
            'columns' => $input->columnNames,
        ];
    }

    /**
     * Print limit box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    private function getLimitOptions(SelectDqDto $input): array
    {
        return [
            'value' => $this->utils()->html($input->limit),
        ];
    }

    /**
     * Print text length box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    private function getLengthOptions(SelectDqDto $input): array
    {
        return [
            'value' => $input->textLength === 0 ? 0 : $this->utils()->html($input->textLength),
        ];
    }

    /**
     * Print action box in select
     *
     * @param SelectDqDto $input
     *
     * @return array
     */
    // private function getActionOptions(SelectDqDto $input)
    // {
    //     $columns = [];
    //     foreach ($input->table->indexes as $index) {
    //         $current_key = \reset($index->columns);
    //         if ($index->type != 'FULLTEXT' && $current_key) {
    //             $columns[$current_key] = 1;
    //         }
    //     }
    //     $columns[''] = 1;
    //     return ['columns' => $columns];
    // }

    /**
     * Print command box in select
     *
     * @return bool whether to print default commands
     */
    // private function getCommandOptions()
    // {
    //     return !$this->engine()->isInformationSchema($this->engine()->database());
    // }

    /**
     * Print import box in select
     *
     * @return bool whether to print default import
     */
    // private function getImportOptions()
    // {
    //     return !$this->engine()->isInformationSchema($this->engine()->database());
    // }

    /**
     * Print extra text in the end of a select form
     *
     * @param array $emailColumns Columns holding e-mails
     * @param array $columns Selectable columns
     *
     * @return array
     */
    // private function getEmailOptions(array $emailColumns, array $columns)
    // {}

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    public function setQueryOptions(SelectDqDto $input): void
    {
        $input->options = [
            'columns' => $this->getColumnsOptions($input),
            'filters' => $this->getFiltersOptions($input),
            'sorting' => $this->getSortingOptions($input),
            'limit' => $this->getLimitOptions($input),
            'length' => $this->getLengthOptions($input),
            // 'action' => $this->getActionOptions($input),
        ];
    }
}
