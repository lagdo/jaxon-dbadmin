<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields\Sorters;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultHeaderDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Html\Component\Component;

use function Jaxon\rq;

class ResultUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param QueryResultRowDto $row
     *
     * @return Component
     */
    private function _resultRowContent(QueryResultRowDto $row): Component
    {
        return $this->ui->list(
            $this->ui->tableDataCell($row->rowMenu, ['style' => 'width:30px']),
            $this->ui->each($row->columns, function(array $column) {
                $html = $column['html'];
                if ($column['value'] === null || !isset($column['foreign'])) {
                    return $this->ui->tableDataCell($this->ui->html($html));
                }

                /** @var ForeignKeyDto */
                $foreignKey = $column['foreign'];
                $tableName = $foreignKey->table;
                $columnName = $foreignKey->target[0];
                $columnValue = $column['value'];

                return $this->ui->tableDataCell(
                    $this->ui->a($column['foreignLabel'] ?? $html)
                        ->setHref('javascript:void(0)')
                        ->jxnClick(rq(Select::class)->foreign($tableName, $columnName, $columnValue))
                );
            })
        );
    }

    /**
     * @param QueryResultRowDto $row
     *
     * @return string
     */
    public function resultRowContent(QueryResultRowDto $row): string
    {
        return $this->ui->build($this->_resultRowContent($row));
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

        return $this->ui->build(
            $this->ui->table(
                $this->ui->tableHead(
                    $this->ui->tableRow(
                        $this->ui->tableHeadCell(['style' => 'width:30px']),
                        $this->ui->each($headers, $this->tableHeadCell(...))
                    )
                ),
                $this->ui->tableBody(
                    $this->ui->each($rows, fn(QueryResultRowDto $row) =>
                        $this->ui->tableRow($this->_resultRowContent($row))
                            ->when($row->editValues !== null, fn($tr) =>
                                $tr->tbnBindApp($rqResultRow, $row->bagId)))
                )
            )->border()
        );
    }
}
