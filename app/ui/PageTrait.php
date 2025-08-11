<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\UiBuilder\BuilderInterface;

trait PageTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    private function getTableCell($content): mixed
    {
        $ui = $this->builder();
        if (!is_array($content)) {
            return $ui->td($ui->html($content));
        }

        if(!isset($content['handler']))
        {
            return $ui->td($ui->text($content['label']));
        }

        $element = $ui->td();
        if(isset($content['props']))
        {
            $element->setAttributes($content['props']);
        }

        $element->children(
            $ui->a($ui->text($content['label']))
                ->setAttributes(['href' => 'javascript:void(0)'])
                ->jxnClick($content['handler'])
        );
        return $element;
    }

    /**
     * @param array $content
     * @param string $counterId
     *
     * @return mixed
     */
    private function makeTable(array $content, string $counterId): mixed
    {
        $headers = $content['headers'] ?? [];
        $details = $content['details'] ?? [];
        $ui = $this->builder();
        return $ui->table(
            $ui->thead(
                $ui->when($counterId !== '', fn() =>
                    $ui->th(
                        $ui->checkbox()
                            ->addClass('dbadmin-table-checkbox')
                            ->setId("dbadmin-table-$counterId-all")
                    )
                ),
                $ui->each($headers, fn($header) =>
                    $ui->th($ui->html($header))
                ),
            ),
            $ui->body(
                $ui->each($details, fn($detailGroup) =>
                    $ui->tr(
                        $ui->when($counterId !== '', fn() =>
                            $ui->td(
                                $ui->checkbox()
                                    ->addClass("dbadmin-table-$counterId")
                                    ->setName("{$counterId}[]")
                            )
                        ),
                        $ui->each($detailGroup, fn($detail) =>
                            $this->getTableCell($detail ?? '')
                        )
                    )
                ),
            ),
        )
        ->responsive()
        ->style('bordered');
    }

    /**
     * @param array $pageContent
     * @param string $counterId
     *
     * @return string
     */
    public function pageContent(array $pageContent, string $counterId = ''): string
    {
        $ui = $this->builder();
        return $ui->build(
            $this->makeTable($pageContent, $counterId),
            $ui->when($counterId !== '', function() use($ui, $counterId) {
                $message = "Selected (<span id=\"dbadmin-table-{$counterId}-count\">0</span>)";
                return $ui->panel(
                    $ui->panelBody($ui->html($message))
                );
            })
        );
    }
}
