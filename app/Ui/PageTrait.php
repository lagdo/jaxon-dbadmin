<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\HtmlComponent;

use function array_shift;
use function array_values;
use function count;
use function is_array;

trait PageTrait
{
    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @return Tab
     */
    abstract protected function tab(): Tab;

    /**
     * @param array $menus
     *
     * @return HtmlComponent
     */
    private function buttonMenuComponent(array $menus): HtmlComponent
    {
        $menu = array_shift($menus);
        return $this->ui->buttonGroup(
            $this->ui->button($menu['label'])
                ->primary()
                ->jxnClick($menu['handler']),
            $this->ui->dropdownButton()
                ->primary(),
            $this->ui->when(count($menus) > 0, fn() =>
                $this->ui->dropdownMenu(
                    $this->ui->each($menus, fn($menu) =>
                        $this->ui->dropdownMenuItem($menu['label'])
                            ->jxnClick($menu['handler'])
                    )
                )->setStyle('position:relative;')
            )
        );
    }

    /**
     * @param array $menus
     *
     * @return string
     */
    public function buttonMenu(array $menus): string
    {
        return $this->ui->build($this->buttonMenuComponent($menus));
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    private function getTableCell(mixed $content): mixed
    {
        if (!is_array($content)) {
            return $this->ui->tableDataCell($this->ui->html($content));
        }

        $element = $this->ui->tableDataCell();
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
     * @param string $contentType
     *
     * @return string
     */
    private function counter(string $contentType = ''): string
    {
        if ($contentType === '') {
            return '';
        }

        $countId = $this->tab()->app()->id("dbadmin-table-{$contentType}-count");
        return "(<span id=\"{$countId}\">0</span>)";
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
        $numbers = $content['numbers'] ?? [];
        $hasMenu = (array_values($details)[0] ?? null)?->menus !== null;

        return $this->ui->table(
            $this->ui->when(count($details) > 0, fn() =>
                $this->ui->tableHead(
                    $this->ui->when($contentType !== '', fn() =>
                        $this->ui->tableHeadCell(
                            $this->ui->checkbox()
                                ->setId($this->tab()->app()->id("dbadmin-table-$contentType-all"))
                        )->addClass('dbadmin-table-checkbox')
                    ),
                    $this->ui->when($hasMenu, fn() =>
                        $this->ui->tableHeadCell($this->ui->html($this->counter($contentType)))
                    ),
                    $this->ui->each($headers, fn($header, $key) =>
                        $this->ui->tableHeadCell($this->ui->html($header))
                            ->when(isset($numbers[$key]), fn(HtmlComponent $elt) =>
                                $elt->setStyle('text-align: right;')
                            )
                    )
                )
            ),
            $this->ui->tableBody(
                $this->ui->each($details, fn(DetailDto $detail) =>
                    $this->ui->tableRow(
                        $this->ui->when($contentType !== '', fn() =>
                            $this->ui->tableDataCell(
                                $this->ui->checkbox()
                                    ->addClass("dbadmin-table-$contentType")
                                    ->setName("{$contentType}[]")
                            )->addClass('dbadmin-table-checkbox')
                        ),
                        $this->ui->when($hasMenu, fn() =>
                            $this->ui->tableDataCell(
                                $this->ui->when($detail->menus !== null, fn() => 
                                    $this->buttonMenuComponent($detail->menus)
                                )
                            )->setStyle('width:60px;')
                        ),
                        $this->ui->each($detail->items, fn($detailItem, $key) =>
                            $this->getTableCell($detailItem ?? '')
                                ->when(isset($numbers[$key]), fn(HtmlComponent $elt) =>
                                    $elt->setStyle('text-align: right;')
                                )
                        )
                    )
                )
            )
        )->border();
    }

    /**
     * @param array $pageContent
     * @param string $contentType
     *
     * @return string
     */
    public function pageContent(array $pageContent, string $contentType = ''): string
    {
        return $this->ui->build($this->makeTable($pageContent, $contentType));
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public function panel(string $content): string
    {
        return $this->ui->build(
            $this->ui->card(
                $this->ui->cardBody(
                    $this->ui->div($content)
                        ->setClass('jaxon-dbadmin-main-content')
                )->setClass('jaxon-dbadmin-main-wrapper')
            )
        );
    }
}
