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
        $html = $this->builder();
        if (!is_array($content)) {
            return $html->td($html->html($content));
        }

        if(!isset($content['handler']))
        {
            return $html->td($html->text($content['label']));
        }

        $element = $html->td();
        if(isset($content['props']))
        {
            $element->setAttributes($content['props']);
        }

        $element->children(
            $html->a($html->text($content['label']))
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
        $html = $this->builder();
        return $html->table(
            $html->thead(
                $html->when($counterId !== '', fn() =>
                    $html->th(
                        $html->checkbox()
                            ->addClass('dbadmin-table-checkbox')
                            ->setId("dbadmin-table-$counterId-all")
                    )
                ),
                $html->each($headers, fn($header) =>
                    $html->th($html->html($header))
                ),
            ),
            $html->body(
                $html->each($details, fn($detailGroup) =>
                    $html->tr(
                        $html->when($counterId !== '', fn() =>
                            $html->td(
                                $html->checkbox()
                                    ->addClass("dbadmin-table-$counterId")
                                    ->setName("{$counterId}[]")
                            )
                        ),
                        $html->each($detailGroup, fn($detail) =>
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
        $html = $this->builder();
        return $html->build(
            $this->makeTable($pageContent, $counterId),
            $html->when($counterId !== '', function() use($html, $counterId) {
                $message = "Selected (<span id=\"dbadmin-table-{$counterId}-count\">0</span>)";
                return $html->panel(
                    $html->panelBody($html->html($message))
                );
            })
        );
    }
}
