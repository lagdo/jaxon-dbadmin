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
    private function _buttonMenu(array $menus): HtmlComponent
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
        return $this->ui->build($this->_buttonMenu($menus));
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
     * @param array $content
     * @param string $contentType
     *
     * @return mixed
     */
    private function makeTable(array $content, string $contentType): mixed
    {
        $headers = $content['headers'] ?: [];
        $details = $content['details'] ?? [];
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
                    $this->ui->when($hasMenu, fn() => $this->ui->tableHeadCell('')),
                    $this->ui->each($headers, fn($header) =>
                        $this->ui->tableHeadCell($this->ui->html($header))
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
                        $this->ui->when($detail->menus !== null, fn() =>
                            $this->ui->tableDataCell(
                                $this->_buttonMenu($detail->menus)
                            )->setStyle('width:60px;')
                        ),
                        $this->ui->each($detail->items, fn($detailItem) =>
                            $this->getTableCell($detailItem ?? '')
                        )
                    )
                )
            )
        )->responsive()
            ->border();
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
     * @param string $contentType
     *
     * @return string
     */
    public function pageFooter(string $contentType = ''): string
    {
        if ($contentType === '') {
            return '';
        }

        $countId = $this->tab()->app()->id("dbadmin-table-{$contentType}-count");
        return $this->ui->html("Selected (<span id=\"{$countId}\">0</span>)");
    }

    /**
     * @param string|array $content
     * @param string|null $style
     *
     * @return string
     */
    public function panel(string|array $content, string|null $style = null): string
    {
        if ($content === '') {
            return '';
        }

        $header = '';
        $body = $content;
        if (is_array($content)) {
            $header = $content['header'];
            $body = $content['body'];
        }

        return $this->ui->build(
            $this->ui->div(
                $this->ui->card(
                    $this->ui->when($header !== '', fn() =>
                        $this->ui->cardHeader($header)
                    ),
                    $this->ui->cardBody($this->ui->div($body))
                )
            )->when($style !== null, fn($element) => $element->setStyle($style))
        );
    }
}
