<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\App\Admin;
use Lagdo\DbAdmin\Ajax\App\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\App\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ajax\App\Page\ServerInfo;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\Jaxon\Builder;

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
    public function sidebar(array $servers, string $default): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
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
                ->col(12)->jxnBind(rq(ServerCommand::class))
                ->end()
                ->col(12)->jxnBind(rq(MenuDatabases::class))
                ->end()
                ->col(12)->jxnBind(rq(MenuSchemas::class))
                ->end()
                ->col(12)->jxnBind(rq(DatabaseCommand::class))
                ->end()
                ->col(12)->jxnBind(rq(MenuSections::class))
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @return string
     */
    public function content(): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()->jxnBind(rq(ServerInfo::class))
            ->end()
            ->row()
                ->col(12)
                    ->span()->jxnBind(rq(Breadcrumbs::class))
                    ->end()
                    ->span()->jxnBind(rq(PageActions::class))
                    ->end()
                ->end()
            ->end()
            ->row()
                ->col(12)->jxnBind(rq(Content::class))
                ->end()
            ->end();
        return $htmlBuilder->build();
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
                    ->addHtml($this->sidebar($servers, $default))
                ->end()
                ->col(9)
                    ->row()->jxnBind(rq(ServerInfo::class))
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()->jxnBind(rq(Breadcrumbs::class))
                            ->end()
                            ->span()->jxnBind(rq(PageActions::class))
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)->jxnBind(rq(Content::class))
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
                    ->option(false, $label)->setClass($optionClass)
                        ->setValue(htmlentities($value))
                    ->end();
        }
        $htmlBuilder
                ->end();
        return $htmlBuilder->build();
    }
}
