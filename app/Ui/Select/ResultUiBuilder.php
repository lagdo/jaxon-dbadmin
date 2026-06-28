<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultHeaderDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Html\Component\Component;
use Lagdo\UiBuilder\Html\Element\Element;

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
     * @param array $column
     *
     * @return Element|Component
     */
    private function tableDataCellValue(array $column): Element|Component
    {
        $html = $column['html'];
        if ($column['value'] === null || !isset($column['foreign'])) {
            return $this->ui->html($html);
        }

        /** @var ForeignKeyDto */
        $foreignKey = $column['foreign'];
        $tableName = $foreignKey->table;
        $columnName = $foreignKey->target[0];

        return $this->ui->a($html)
            ->setHref('javascript:void(0)')
            ->jxnClick(rq(Select::class)->follow($tableName, $columnName, $html));
    }

    /**
     * @param QueryResultRowDto $row
     *
     * @return mixed
     */
    private function _resultRowContent(QueryResultRowDto $row): mixed
    {
        return $this->ui->list(
            $this->ui->tableDataCell($row->rowMenu, ['style' => 'width:30px']),
            $this->ui->each($row->columns, fn(array $column) =>
                $this->ui->tableDataCell($this->tableDataCellValue($column))
            )
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
                        $this->ui->each($headers, fn(QueryResultHeaderDto $header) =>
                            $this->ui->tableHeadCell($header->title)
                        )
                    )
                ),
                $this->ui->tableBody(
                    $this->ui->each($rows, fn(QueryResultRowDto $row) =>
                        $this->ui->tableRow($this->_resultRowContent($row))
                            ->when($row->editValues !== null, fn($tr) =>
                                $tr->tbnBindApp($rqResultRow, $row->bagId)))
                )
            )->responsive()->border()
        );
    }
}
