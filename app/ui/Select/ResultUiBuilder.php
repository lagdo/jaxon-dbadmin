<?php

namespace Lagdo\DbAdmin\Ui\Select;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\Db\Translator;
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
     * @param array $row
     *
     * @return mixed
     */
    private function _resultRowContent(array $row): mixed
    {
        return $this->ui->list(
            $this->ui->td($row['menu'], ['style' => 'width:30px']),
            $this->ui->each($row['cols'], fn($col) =>
                $this->ui->td($col['value'])
            )
        );
    }

    /**
     * @param array $row
     *
     * @return string
     */
    public function resultRowContent(array $row): string
    {
        return $this->ui->build($this->_resultRowContent($row));
    }

    /**
     * @param array $headers
     * @param array $rows
     *
     * @return string
     */
    public function resultSet(array $headers, array $rows): string
    {
        $rqResultRow = rq(ResultRow::class);

        return $this->ui->build(
            $this->ui->table(
                $this->ui->thead(
                    $this->ui->tr(
                        $this->ui->th(['style' => 'width:30px']),
                        $this->ui->each($headers, fn(array $header) =>
                            $this->ui->th($header['title'] ?? '')
                        )
                    )
                ),
                $this->ui->tbody(
                    $this->ui->each($rows, fn(array $row) =>
                        $this->ui->tr($this->_resultRowContent($row))
                            ->when($row['editId'] > 0, fn($tr) =>
                                $tr->tbnBind($rqResultRow, $row['editItemId'])))
                )
            )->responsive(true)->look('bordered')
        );
    }
}
