<?php

namespace Lagdo\DbAdmin\Ui\Command;

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
                $this->ui->panel(
                    $this->ui->panelBody($this->ui->span($results['message']))
                        ->setStyle('padding:5px 15px')
                )->look('info')
            ),
            $this->ui->when($resultsAreTruncated, fn() =>
                $this->ui->panel(
                    $this->ui->panelBody($this->ui->span($truncatedMessage))
                        ->setStyle('padding:5px 15px')
                )->look('info')
            ),
            $this->ui->each($truncatedResults, function($result) {
                $query = $result['query'];
                $messages = $result['messages'];
                $errors = $result['errors'];
                $select = $result['select'] ?? []; // Data returned by select queries.

                return $this->ui->row(
                    $this->ui->col(
                        $this->ui->when(count($errors) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($query)),
                                $this->ui->panelBody(
                                    $this->ui->each($errors, $this->ui->span(...))
                                )->setStyle('padding:5px 15px')
                            )->look('danger')
                        ),
                        $this->ui->when(count($messages) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($query)),
                                $this->ui->panelBody(
                                    $this->ui->each($messages, $this->ui->span(...))
                                )->setStyle('padding:5px 15px')
                            )->look('success')
                        ),
                        $this->ui->when(count($select) > 0, fn() =>
                            $this->ui->table(
                                $this->ui->thead(
                                    $this->ui->tr(
                                        $this->ui->each($select['headers'], fn($header) =>
                                            $this->ui->th($this->ui->html($header))
                                        )
                                    )
                                ),
                                $this->ui->tbody(
                                    $this->ui->each($select['details'], fn($details) =>
                                        $this->ui->tr(
                                            $this->ui->each($details, fn($detail) =>
                                                $this->ui->td($this->ui->html($detail))
                                            )
                                        )
                                    )
                                )
                            )->responsive(true)
                                ->look('bordered')
                                ->setStyle('margin-top:2px')
                        ),
                    )->width(12)
                );
            })
        );
    }
}
