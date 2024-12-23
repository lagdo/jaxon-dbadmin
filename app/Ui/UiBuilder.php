<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin;
use Lagdo\DbAdmin\App\Ajax\Menu\Db;
use Lagdo\DbAdmin\App\Ajax\Menu\DbActions;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Menu\Server;
use Lagdo\DbAdmin\App\Ajax\Menu\ServerActions;
use Lagdo\DbAdmin\App\Ajax\Page\Breadcrumbs;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\Jaxon\Builder;

use function count;
use function htmlentities;
use function Jaxon\pm;
use function Jaxon\rq;

class UiBuilder
{
    use Traits\MenuTrait;
    use Traits\MainTrait;
    use Traits\ServerTrait;
    use Traits\DatabaseTrait;
    use Traits\QueryTrait;
    use Traits\TableTrait;
    use Traits\SelectTrait;

    /**
     * @var InputBuilder
     */
    protected $inputBuilder;

    /**
     * @param Translator $trans
     */
    public function __construct(protected Translator $trans)
    {
        $this->inputBuilder = new InputBuilder($this->trans);
    }

    /**
     * @return string
     */
    public function formRowTag(): string
    {
        return Builder::new()->formRowTag();
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function formRowClass(string $class = ''): string
    {
        return Builder::new()->formRowClass($class);
    }

    /**
     * @param array $servers
     * @param string $default
     *
     * @return string
     */
    public function home(array $servers, string $default): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()->setId('jaxon-dbadmin')
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->formSelect()->setId('jaxon-dbadmin-dbhost-select');
        foreach($servers as $serverId => $server)
        {
            $htmlBuilder
                                    ->option($serverId === $default, $server['name'])->setValue($serverId)
                                    ->end();
        }
        $htmlBuilder
                                ->end()
                                ->button()->btnPrimary()->setClass('btn-select')
                                    ->jxnClick(rq(Admin::class)->server(pm()->select('jaxon-dbadmin-dbhost-select')))
                                    ->addText('Show')
                                ->end()
                            ->end()
                        ->end()
                        ->col(12)->jxnShow(rq(ServerActions::class))
                        ->end()
                        ->col(12)->jxnShow(rq(DbList::class))
                        ->end()
                        ->col(12)->jxnShow(rq(SchemaList::class))
                        ->end()
                        ->col(12)->jxnShow(rq(DbActions::class))
                        ->end()
                        ->col(12)->jxnShow(rq(Db::class))
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->row()->jxnShow(rq(Server::class))
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()->jxnShow(rq(Breadcrumbs::class))
                            ->end()
                            ->span()->jxnShow(rq(PageActions::class))
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)->jxnShow(rq(Content::class))
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $options
     * @param string $optionClass
     * @param bool $useKeys
     *
     * @return string
     */
    public function htmlSelect(array $options, string $optionClass, bool $useKeys = false): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
                ->formSelect();
        foreach($options as $key => $label)
        {
            $value = $useKeys ? $key : $label;
            $htmlBuilder
                    ->option(false, $label)->setClass($optionClass)->setValue(htmlentities($value))
                    ->end();
        }
        $htmlBuilder
                ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $pages
     *
     * @return string
     */
    public function pagination(array $pages): string
    {
        // There must be at least 2 pages to show in pagination.
        if (count($pages) < 4) {
            return '';
        }

        $htmlBuilder = Builder::new();
        $htmlBuilder
                ->pagination();
        foreach($pages as $page)
        {
            if ($page->type === 'disabled') {
                $htmlBuilder
                    ->paginationDisabledItem()->addHtml($page->text)
                    ->end();
            } elseif ($page->type === 'current') {
                $htmlBuilder
                    ->paginationActiveItem()->addHtml($page->text)
                    ->end();
            } else {
                $htmlBuilder
                    ->paginationItem(['href' => 'javascript:void(0)', 'onclick' => $page->call])
                        ->addHtml($page->text)
                    ->end();
            }
        }
        $htmlBuilder
                ->end();
        return $htmlBuilder->build();
    }
}
