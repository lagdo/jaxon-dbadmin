<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\DbAdmin\Ui\TabEditor;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function Jaxon\form;
use function Jaxon\jo;
use function Jaxon\rq;

class QueryUiBuilder
{
    use PageTrait;
    use QueryResultsTrait;

    /**
     * @var int
     */
    private int $defaultLimit = 20;

    /**
     * @var string
     */
    private const QUERY_TEXT_CLASS = 'dbadmin-main-command-query';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param ServerConfig $config
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected ServerConfig $config)
    {}

    /**
     * @return string
     */
    private function queryFormId(): string
    {
        return TabEditor::id('dbadmin-main-command-form');
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function queryButtons(JxnCall $rqQuery): mixed
    {
        $queryText = jo('jaxon.dbadmin')->getQueryText();
        $queryValues = form($this->queryFormId());

        return $this->ui->buttonGroup(
        $this->ui->button(
                $this->ui->text($this->trans->lang('Execute'))
            )->primary()
                ->jxnClick($rqQuery->exec($queryText, $queryValues)),
            $this->ui->button(
                $this->ui->text($this->trans->lang('Save'))
            )->secondary()
                ->jxnClick(rq(Query\FavoriteFunc::class)->add($queryText))
        )->fullWidth();
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function actions(JxnCall $rqQuery): mixed
    {
        return $this->ui->form(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Limit rows'))
                        ),
                        $this->ui->input()
                            ->setName('limit')
                            ->setType('number')
                            ->setValue($this->defaultLimit)
                    )
                )->width(2),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )->width(3),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                ) ->width(3),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->queryButtons($rqQuery)
                        )
                        ->width(8),
                        $this->ui->col()
                            ->width(4)
                            ->tbnBindEditor(rq(Query\Duration::class))
                    )
                )->width(4)
            )
        )->horizontal(true)
            ->wrapped(true)
            ->setId($this->queryFormId());
    }

    /**
     * @return string
     */
    public function commandDetailsId(): string
    {
        return TabEditor::id('dbadmin-main-command-details');
    }

    /**
     * @return string
     */
    public function commandEditorId(): string
    {
        return TabEditor::id(self::QUERY_TEXT_CLASS);
    }

    /**
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function editorTabNav(bool $active): HtmlComponent
    {
        return $this->ui->tabNavItem($this->trans->lang('Editor'))
            ->target(TabEditor::wrapperId())
            ->setId(TabEditor::titleId())
            ->active($active)
            ->jxnOn('click', jo('jaxon.dbadmin')
                ->onEditorTabClick(TabApp::current(), TabEditor::current()));
    }

    /**
     * @return string
     */
    public function editorTabNavHtml(): string
    {
        return $this->ui->build(
            $this->editorTabNav(false)
        );
    }

    /**
     * @param JxnCall $rqQuery
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function editorTabContent(JxnCall $rqQuery, bool $active): HtmlComponent
    {
        return $this->ui->tabContentItem(
                    $this->ui->row(
                        $this->ui->col()->width(12)->setId($this->commandDetailsId()),
                        $this->ui->col(
                            $this->ui->row(
                                $this->ui->col(
                                    $this->ui->panel(
                                        $this->ui->panelBody(
                                            $this->ui->div()
                                                ->setId($this->commandEditorId())
                                                ->setClass(self::QUERY_TEXT_CLASS)
                                        )->setClass('sql-command-editor-panel')
                                            ->setStyle('padding: 0 1px;')
                                    )->look('default')
                                        ->setStyle('padding: 5px;')
                                )->width(12)
                            )
                        )->width(12),
                        $this->ui->col(
                            $this->actions($rqQuery)
                        )->width(12),
                        $this->ui->col()
                            ->width(12)
                            ->tbnBindEditor(rq(Query\ResultSet::class))
                    )
                )->setId(TabEditor::wrapperId())
                    ->active($active);
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return string
     */
    public function editorTabContentHtml(JxnCall $rqQuery): string
    {
        return $this->ui->build(
            $this->editorTabContent($rqQuery, false)
        );
    }

    /**
     * @return string
     */
    public function editorTabNavWrapperId(): string
    {
        return TabApp::id("dbadmin-query-editor-tab-nav");
    }

    /**
     * @return string
     */
    public function editorTabContentWrapperId(): string
    {
        return TabApp::id("dbadmin-query-editor-tab-content");
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return string
     */
    public function command(JxnCall $rqQuery): string
    {
        $menuEntries = [[
            'label' => '<i class="fa fa-plus"></i>',
            'handler' => $rqQuery->addTab(),
        ], [
            'label' => $this->trans->lang('Delete'),
            'handler' => $rqQuery->delTab()
                ->confirm($this->trans->lang('Delete this tab?')),
        ]];
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->tableMenu($menuEntries),
                )->setClass('jaxon-dbadmin-tabs-layout_button'),
                $this->ui->col(
                    $this->ui->tabNav(
                        $this->ui->when($this->config->favoriteEnabled(), fn() =>
                            $this->ui->tabNavItem($this->trans->lang('History'))
                                ->target(TabEditor::id("tab-content-query-history"))
                                ->active(false)
                        ),
                        $this->ui->when($this->config->historyEnabled(), fn() =>
                            $this->ui->tabNavItem($this->trans->lang('Favorites'))
                                ->target(TabEditor::id("tab-content-query-favorite"))
                                ->active(false)
                        ),
                        $this->editorTabNav(true)
                    )->setId($this->editorTabNavWrapperId())
                        ->setStyle('margin-bottom: 5px;')
                )->setClass('jaxon-dbadmin-tabs-layout_header')
            )->setClass('jaxon-dbadmin-tabs-layout'),
            $this->ui->tabContent(
                $this->ui->when($this->config->favoriteEnabled(), fn() =>
                    $this->ui->tabContentItem()
                        ->tbnBindApp(rq(Query\History::class))
                        ->setId(TabEditor::id("tab-content-query-history"))
                        ->active(false)),
                $this->ui->when($this->config->historyEnabled(), fn() =>
                    $this->ui->tabContentItem()
                        ->tbnBindApp(rq(Query\Favorite::class))
                        ->setId(TabEditor::id("tab-content-query-favorite"))
                        ->active(false)),
                $this->editorTabContent($rqQuery, true)
            )->setId($this->editorTabContentWrapperId())
        );
    }
}
