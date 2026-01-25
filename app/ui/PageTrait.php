<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ui\Tab;
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

        $element->contents(
            $this->ui->a($this->ui->text($content['label']))
                ->setAttributes(['href' => 'javascript:void(0)'])
                ->jxnClick($content['handler'])
        );
        return $element;
    }

    /**
     * @param array $content
     * @param string $contentType
     *
     * @return mixed
     */
    private function makeTable(array $content, string $contentType): mixed
    {
        $headers = $content['headers'] ?: [];
        $details = $content['details'] ?? [];
        return $this->ui->table(
            $this->ui->thead(
                $this->ui->when($contentType !== '', fn() =>
                    $this->ui->th(
                        $this->ui->checkbox()
                            ->setId(Tab::id("dbadmin-table-$contentType-all"))
                    )->addClass('dbadmin-table-checkbox')
                ),
                $this->ui->each($headers, fn($header) =>
                    $this->ui->th($this->ui->html($header))
                ),
            ),
            $this->ui->body(
                $this->ui->each($details, fn($detailGroup) =>
                    $this->ui->tr(
                        $this->ui->when($contentType !== '', fn() =>
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->addClass("dbadmin-table-$contentType")
                                    ->setName("{$contentType}[]")
                            )->addClass('dbadmin-table-checkbox')
                        ),
                        $this->ui->each($detailGroup, fn($detail, $title) =>
                            $this->getTableCell($title, $detail ?? '')
                        )
                    )
                ),
            ),
        )->responsive()->look('bordered');
    }

    /**
     * @param array $pageContent
     * @param string $contentType
     *
     * @return string
     */
    public function pageContent(array $pageContent, string $contentType = ''): string
    {
        $countId = Tab::id("dbadmin-table-{$contentType}-count");
        return $this->ui->build(
            $this->makeTable($pageContent, $contentType),
            $this->ui->when($contentType !== '', function() use($countId) {
                $message = "Selected (<span id=\"{$countId}\">0</span>)";
                return $this->ui->panel(
                    $this->ui->panelBody($this->ui->html($message))
                );
            })
        );
    }
}
