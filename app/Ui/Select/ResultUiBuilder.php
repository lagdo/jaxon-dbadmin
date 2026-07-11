<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields\Sorters;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultHeaderDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectRowsetDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Html\Component\Component;
use Closure;

use function array_filter;
use function Jaxon\rq;
use function preg_match;

class ResultUiBuilder
{
    /**
     * Filter for CTE columns in select results.
     *
     * @var Closure
     */
    private Closure $cteFilter;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {
        $this->cteFilter = fn(string $column) => !preg_match('/^_dbadmin_cte_/', $column);
    }

    /**
     * @param array<QueryResultHeaderDto> $headers
     * @param QueryResultRowDto $row
     *
     * @return Component
     */
    private function _resultRowContent(array $headers, QueryResultRowDto $row): Component
    {
        $columns = array_filter($row->columns, $this->cteFilter, ARRAY_FILTER_USE_KEY);

        return $this->ui->list(
            $this->ui->tableDataCell($row->rowMenu, ['style' => 'width:30px']),
            $this->ui->each($columns, function(array $column, string $name) use($headers, $row) {
                $html = $column['html'];
                $header = $headers[$name] ?? null;
                if ($column['value'] === null || $header?->foreignKey === null) {
                    return $this->ui->tableDataCell($this->ui->html($html));
                }

                $foreignKey = $header->foreignKey;
                $tableName = $foreignKey->table;
                $columnName = $foreignKey->target[0];
                $columnValue = $column['value'];
                $cteColumn = $row->columns["_dbadmin_cte_{$name}_label"] ?? null;

                return $this->ui->tableDataCell(
                    $this->ui->a($cteColumn['html'] ?? $html)
                        ->setHref('javascript:void(0)')
                        ->jxnClick(rq(Select::class)->foreign($tableName, $columnName, $columnValue))
                );
            })
        );
    }

    /**
     * @param SelectRowsetDto $rowset
     *
     * @return string
     */
    public function resultRowContent(SelectRowsetDto $rowset): string
    {
        return $this->ui->build($this->_resultRowContent($rowset->headers, $rowset->rows[0]));
    }

    /**
     * @param QueryResultHeaderDto $header
     *
     * @return Component
     */
    private function tableHeadCell(QueryResultHeaderDto $header): Component
    {
        $rqSorter = rq(Sorters::class);

        return $this->ui->tableHeadCell(
            $this->ui->div($this->ui->html($header->title)),
            $this->ui->when($header->column->name !== '', fn() =>
                $this->ui->list(
                    $this->ui->div(
                        $this->ui->a($this->ui->html('&#9651;'))
                            ->setAttributes(['href' => 'javascript:void(0)'])
                            ->setStyle('text-decoration: none;')
                            ->jxnClick($rqSorter->upsert($header->column->name, false)),
                        $this->ui->html('&nbsp;'),
                        $this->ui->a($this->ui->html('&#9661;'))
                            ->setAttributes(['href' => 'javascript:void(0)'])
                            ->setStyle('text-decoration: none;')
                            ->jxnClick($rqSorter->upsert($header->column->name, true)),
                        $this->ui->html('&nbsp;'),
                        $this->ui->a($this->ui->html('&#8553;'))
                            ->setAttributes(['href' => 'javascript:void(0)'])
                            ->setStyle('text-decoration: none;')
                            ->jxnClick($rqSorter->remove($header->column->name))
                    )
                )
            )
        );
    }

    /**
     * @param array<QueryResultHeaderDto> $headers
     * @param array<QueryResultRowDto> $rows
     *
     * @return string
     */
    public function resultSet(array $headers, array $rows): string
    {
        $rqResultRow = rq(ResultRow::class);
        $tableHeaders = array_filter($headers, $this->cteFilter, ARRAY_FILTER_USE_KEY);

        return $this->ui->build(
            $this->ui->table(
                $this->ui->tableHead(
                    $this->ui->tableRow(
                        $this->ui->tableHeadCell(['style' => 'width:30px']),
                        $this->ui->each($tableHeaders, $this->tableHeadCell(...))
                    )
                ),
                $this->ui->tableBody(
                    $this->ui->each($rows, fn(QueryResultRowDto $row) =>
                        $this->ui->tableRow($this->_resultRowContent($headers, $row))
                            ->when($row->editValues !== null, fn($tr) =>
                                $tr->tbnBindApp($rqResultRow, $row->bagId)))
                )
            )->border()
        );
    }
}
