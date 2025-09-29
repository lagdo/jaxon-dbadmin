<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\UiBuilder\BuilderInterface;

use function is_array;

trait PageTrait
{
    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @param string $title
     * @param mixed $content
     *
     * @return mixed
     */
    private function getTableCell(string $title, $content): mixed
    {
        if (!is_array($content)) {
            return $title !== 'menu' ?
                $this->ui->td($this->ui->html($content)) :
                $this->ui->td($this->ui->html($content))
                    ->setStyle('width:50px;');
        }

        if(!isset($content['handler']))
        {
            return $this->ui->td($this->ui->text($content['label']));
        }

        $element = $this->ui->td();
        if(isset($content['props']))
        {
            $element->setAttributes($content['props']);
        }

        $element->children(
            $this->ui->a($this->ui->text($content['label']))
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
        $headers = $content['headers'] ?: [];
        $details = $content['details'] ?? [];
        return $this->ui->table(
            $this->ui->thead(
                $this->ui->when($counterId !== '', fn() =>
                    $this->ui->th(
                        $this->ui->checkbox()
                            ->addClass('dbadmin-table-checkbox')
                            ->setId("dbadmin-table-$counterId-all")
                    )
                ),
                $this->ui->each($headers, fn($header) =>
                    $this->ui->th($this->ui->html($header))
                ),
            ),
            $this->ui->body(
                $this->ui->each($details, fn($detailGroup) =>
                    $this->ui->tr(
                        $this->ui->when($counterId !== '', fn() =>
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->addClass("dbadmin-table-$counterId")
                                    ->setName("{$counterId}[]")
                            )
                        ),
                        $this->ui->each($detailGroup, fn($detail, $title) =>
                            $this->getTableCell($title, $detail ?? '')
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
        return $this->ui->build(
            $this->makeTable($pageContent, $counterId),
            $this->ui->when($counterId !== '', function() use($counterId) {
                $message = "Selected (<span id=\"dbadmin-table-{$counterId}-count\">0</span>)";
                return $this->ui->panel(
                    $this->ui->panelBody($this->ui->html($message))
                );
            })
        );
    }
}
