<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

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
            $this->ui->dropdownItem()->look('primary'),
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
            return $this->ui->td($this->ui->html($content));
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
        $hasMenu = (array_values($details)[0] ?? null)?->menus !== null;

        return $this->ui->table(
            $this->ui->when(count($details) > 0, fn() =>
                $this->ui->thead(
                    $this->ui->when($contentType !== '', fn() =>
                        $this->ui->th(
                            $this->ui->checkbox()
                                ->setId($this->tab()->app()->id("dbadmin-table-$contentType-all"))
                        )->addClass('dbadmin-table-checkbox')
                    ),
                    $this->ui->when($hasMenu, fn() => $this->ui->th('')),
                    $this->ui->each($headers, fn($header) =>
                        $this->ui->th($this->ui->html($header))
                    )
                ),
            ),
            $this->ui->body(
                $this->ui->each($details, fn(DetailDto $detail) =>
                    $this->ui->tr(
                        $this->ui->when($contentType !== '', fn() =>
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->addClass("dbadmin-table-$contentType")
                                    ->setName("{$contentType}[]")
                            )->addClass('dbadmin-table-checkbox')
                        ),
                        $this->ui->when($detail->menus !== null, fn() =>
                            $this->ui->td(
                                $this->_buttonMenu($detail->menus)
                            )->setStyle('width:60px;')
                        ),
                        $this->ui->each($detail->items, fn($detailItem) =>
                            $this->getTableCell($detailItem ?? '')
                        )
                    )
                )
            )
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
     * @param string $content
     * @param bool $grow
     *
     * @return string
     */
    public function panel(string $content, bool $grow): string
    {
        return $content === '' ? '' :
            $this->ui->build(
                $this->ui->div(
                    $this->ui->panel(
                        $this->ui->panelBody(
                            $this->ui->div($content)
                                ->when($grow, fn($element) =>
                                    $element->addClass('jaxon-dbadmin-scrollable-content'))
                        )->when($grow, fn($element) => $element->addClass('full-height'))
                    )->when($grow, fn($element) => $element->addClass('full-height'))
                )->when($grow, fn($element) =>
                    $element->addClass('jaxon-dbadmin-column-flexible'))
            );
    }
}
