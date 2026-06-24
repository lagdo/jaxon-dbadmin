<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\RowsetDto;

use function array_slice;
use function count;

trait QueryResultTrait
{
    /**
     * @param ExecResultDto $result
     *
     * @return string
     */
    public function results(ExecResultDto $result): string
    {
        $resulsets = $result->rowsets;
        $truncatedRowsets = $resulsets;
        $truncatedMessage = $this->trans->lang('Showing the %d first entries in the results.', 20);
        $resultsAreTruncated = false;
        if (count($resulsets) > 12) {
            $truncatedRowsets = array_slice($resulsets, 0, 10);
            $resultsAreTruncated = true;
        }

        return $this->ui->build(
            $this->ui->when($result->message !== null, fn() =>
                $this->ui->alert($result->message)->info()->setStyle('padding:5px 15px')
            ),
            $this->ui->when($resultsAreTruncated, fn() =>
                $this->ui->alert($truncatedMessage)->info()->setStyle('padding:5px 15px')
            ),
            $this->ui->each($truncatedRowsets, fn(RowsetDto $rowset) =>
                $this->ui->card(
                    $this->ui->cardBody(
                        $this->ui->alert($rowset->query),
                        $this->ui->when($rowset->error !== null, fn() =>
                            $this->ui->alert($rowset->error)->danger()),
                        $this->ui->when($rowset->message !== null, fn() =>
                            $this->ui->alert($rowset->message)->success()),
                        $this->ui->when($rowset->rowCount > 0, fn() =>
                            $this->ui->table(
                                $this->ui->tableHead(
                                    $this->ui->tableRow(
                                        $this->ui->each($rowset->headers, fn(string $header) =>
                                            $this->ui->tableHeadCell($this->ui->html($header))
                                        )
                                    )
                                ),
                                $this->ui->tableBody(
                                    $this->ui->each($rowset->rows, fn(array $row) =>
                                        $this->ui->tableRow(
                                            $this->ui->each($row, fn(string $value) =>
                                                $this->ui->tableDataCell($this->ui->html($value))
                                            )
                                        )
                                    )
                                )
                            )->responsive()
                                ->border()
                                ->setStyle('margin-top:2px')
                        )
                    )
                )
            )
        );
    }
}
