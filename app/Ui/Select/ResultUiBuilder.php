<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultHeaderDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

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
     * @return mixed
     */
    private function _resultRowContent(QueryResultRowDto $row): mixed
    {
        return $this->ui->list(
            $this->ui->tableDataCell($row->rowMenu, ['style' => 'width:30px']),
            $this->ui->each($row->columns, fn(array $column) =>
                $this->ui->tableDataCell($column['value'])
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
