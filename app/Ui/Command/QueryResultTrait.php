<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use function array_slice;
use function count;

trait QueryResultTrait
{
    /**
     * @param array $results
     *
     * @return string
     */
    public function results(array $results): string
    {
        $truncatedResults = $results['results'];
        $truncatedMessage = $this->trans->lang('Showing the %d first entries in the results.', 20);
        $resultsAreTruncated = false;
        if (count($results['results']) > 12) {
            $truncatedResults = array_slice($results['results'], 0, 10);
            $resultsAreTruncated = true;
        }

        return $this->ui->build(
            $this->ui->when(isset($results['message']), fn() =>
                $this->ui->alert($results['message'])->info()->setStyle('padding:5px 15px')
            ),
            $this->ui->when($resultsAreTruncated, fn() =>
                $this->ui->alert($truncatedMessage)->info()->setStyle('padding:5px 15px')
            ),
            $this->ui->each($truncatedResults, function(array $result) {
                $select = $result['select'] ?? []; // Data returned by select queries.

                return $this->ui->card(
                    $this->ui->cardBody(
                        $this->ui->alert($result['query']),
                        $this->ui->each($result['errors'], fn(string $error) =>
                            $this->ui->alert($error)->danger()),
                        $this->ui->each($result['messages'], fn(string $message) =>
                            $this->ui->alert($message)->success()),
                        $this->ui->when(count($select) > 0, fn() =>
                            $this->ui->table(
                                $this->ui->tableHead(
                                    $this->ui->tableRow(
                                        $this->ui->each($select['headers'], fn($header) =>
                                            $this->ui->tableHeadCell($this->ui->html($header))
                                        )
                                    )
                                ),
                                $this->ui->tableBody(
                                    $this->ui->each($select['details'], fn($details) =>
                                        $this->ui->tableRow(
                                            $this->ui->each($details, fn($detail) =>
                                                $this->ui->tableDataCell($this->ui->html($detail))
                                            )
                                        )
                                    )
                                )
                            )->responsive()
                                ->border()
                                ->setStyle('margin-top:2px')
                        )
                    )
                );
            })
        );
    }
}
