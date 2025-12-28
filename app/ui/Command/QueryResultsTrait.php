<?php

namespace Lagdo\DbAdmin\Ui\Command;

use function count;

trait QueryResultsTrait
{
    /**
     * @param array $results
     *
     * @return string
     */
    public function results(array $results): string
    {
        return $this->ui->build(
            $this->ui->each($results, function($result) {
                $query = $result['query'];
                $messages = $result['messages'];
                $errors = $result['errors'];
                // Data returned by select queries.
                $select = $result['select'] ?? [];

                return $this->ui->row(
                    $this->ui->col(
                        $this->ui->when(count($errors) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($query)),
                                $this->ui->panelBody(
                                    $this->ui->each($errors, fn($error) =>
                                        $this->ui->span($error)
                                    )
                                )->setStyle('padding:5px 15px')
                            )->look('danger')
                        ),
                        $this->ui->when(count($messages) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($query)),
                                $this->ui->panelBody(
                                    $this->ui->each($messages, fn($message) =>
                                        $this->ui->span($message)
                                    )
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
