<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\HtmlComponent;

use function Jaxon\jo;
use function Jaxon\pm;
use function Jaxon\rq;

class QueryUiBuilder
{
    use PageTrait;
    use QueryResultTrait;

    /**
     * @var int
     */
    private int $defaultLimit = 20;

    /**
     * @var bool
     */
    private bool $canSaveQuery = false;

    /**
     * @var string
     */
    private const QUERY_TEXT_CLASS = 'dbadmin-main-command-query';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param DatabaseConfigProvider $config
     * @param Tab $tab
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui,
        protected DatabaseConfigProvider $config, protected Tab $tab)
    {}

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @param bool $canSaveQuery
     *
     * @return self
     */
    public function canSaveQuery(bool $canSaveQuery): self
    {
        $this->canSaveQuery = $canSaveQuery;
        return $this;
    }

    /**
     * @return string
     */
    private function queryFormId(): string
    {
        return $this->tab()->editor()->id('dbadmin-main-command-form');
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function queryButtons(JxnCall $rqQuery): mixed
    {
        $queryText = jo('jaxon.dbadmin')->getQueryText();
        $execute = $rqQuery->exec($queryText, pm()->form($this->queryFormId()));

        return !$this->canSaveQuery ?
            $this->ui->button(
                $this->ui->text($this->trans->lang('Execute'))
            )->primary()
                ->jxnClick($execute) :
            $this->buttonMenuComponent([[
                'label' => $this->trans->lang('Execute'),
                'handler' => $execute,
            ], [
                'label' => $this->trans->lang('Save'),
                'handler' => rq(Query\FavoriteFunc::class)->add($queryText),
            ]]);
    }

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function actions(JxnCall $rqQuery): mixed
    {
        return $this->ui->form(
            $this->ui->div(
                $this->ui->div(
                    $this->queryButtons($rqQuery)
                ),
                $this->ui->div()
                    ->setStyle('flex-grow: 1;')
                    ->tbnBindEditor(rq(Query\QueryDuration::class)),
                $this->ui->div(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Limit rows'))
                        ),
                        $this->ui->input()
                            ->setName('limit')
                            ->setType('number')
                            ->setValue($this->defaultLimit)
                            ->addClass('dbadmin-number-input dbadmin-no-arrows')
                    )
                ),
                $this->ui->div(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                ),
                $this->ui->div(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                )
            )->setStyle('display: flex; flex-direction: row; gap: 7px;')
        )->wrapped()
            ->setId($this->queryFormId());
    }

    /**
     * @return string
     */
    public function commandEditorId(): string
    {
        return $this->tab()->editor()->id(self::QUERY_TEXT_CLASS);
    }

    /**
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function editorTabNav(bool $active): HtmlComponent
    {
        $appTabId = $this->tab()->app()->current();
        $editorTabId = $this->tab()->editor()->current();

        return $this->ui->tabNavItem($this->trans->lang('Editor'))
            ->target($this->tab()->editor()->wrapperId())
            ->setId($this->tab()->editor()->titleId())
            ->active($active)
            ->jxnClick(jo('jaxon.dbadmin')->onEditorTabClick($appTabId, $editorTabId));
    }

    /**
     * @return string
     */
    public function editorTabNavHtml(): string
    {
        return $this->ui->build(
            $this->editorTabNav(active: false)
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
            $this->ui->div(
                $this->ui->card(
                    $this->ui->cardBody(
                        $this->ui->div()
                            ->setId($this->commandEditorId())
                            ->setClass(self::QUERY_TEXT_CLASS)
                    )->setClass('sql-command-editor-panel')
                        ->setStyle('padding: 0 1px;')
                )->setStyle('padding: 5px;')
            ),
            $this->ui->div(
                $this->actions($rqQuery)
            )->setStyle('margin-top: 7px;'),
            $this->ui->div()
                ->setStyle('margin-top: 15px;')
                ->tbnBindEditor(rq(Query\QueryResult::class))
        )->setId($this->tab()->editor()->wrapperId())
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
        return $this->tab()->app()->id("dbadmin-query-editor-tab-nav");
    }

    /**
     * @return string
     */
    public function editorTabContentWrapperId(): string
    {
        return $this->tab()->app()->id("dbadmin-query-editor-tab-content");
    }

    /**
     * @param JxnCall $rqQuery
     * @param JxnCall $rqEditor
     *
     * @return string
     */
    public function command(JxnCall $rqQuery, JxnCall $rqEditor): string
    {
        $menuEntries = [[
            'label' => '<i class="fa fa-plus"></i>',
            'handler' => $rqEditor->addTab(),
        ], [
            'label' => $this->trans->lang('Clone'),
            'handler' => $rqEditor->cloneTab(),
        ], [
            'label' => $this->trans->lang('Delete'),
            'handler' => $rqEditor->delTab()
                ->confirm($this->trans->lang('Delete this tab?')),
        ]];
        if ($this->canSaveQuery) {
            $tabQueries = jo('jaxon.dbadmin')->getQueries($this->tab()->app()->current(),
                $this->tab()->editor()->page());
            $menuEntries[] = [
                'label' => $this->trans->lang('Save tabs'),
                'handler' => $rqEditor->saveTabs($tabQueries)
                    ->confirm($this->trans->lang('Save this tabs in your preferences?')),
            ];
        }

        $tabsContentId = $this->editorTabContentWrapperId();
        $queriesContentId = $this->tab()->editor()->id("tab-content-query-queries");
        $showQueriesTab = $this->config->hasQueryDatabaseOptions() &&
            ($this->config->queryHistoryEnabled() || $this->config->queryFavoriteEnabled());

        return $this->ui->build(
            $this->ui->div(
                $this->ui->tabs(
                    $this->ui->tabNav(
                        $this->ui->when($showQueriesTab, fn() =>
                            $this->ui->tabNavItem($this->trans->lang('Queries'))
                                ->target($queriesContentId)
                                ->active(false)
                        ),
                        $this->editorTabNav(active: true)
                    )->setId($this->editorTabNavWrapperId())
                )->content($tabsContentId)
                    ->setClass('jaxon-dbadmin-page-header_items'),
                $this->ui->div(
                    $this->buttonMenu($menuEntries),
                )->setClass('jaxon-dbadmin-page-header_menus')
            )->setClass('jaxon-dbadmin-page-header')
                ->setStyle('margin-bottom: 5px;'),
            $this->ui->tabContent(
                $this->ui->when($showQueriesTab, fn() =>
                    $this->ui->tabContentItem()
                        ->tbnBindApp(rq(Query\Queries::class))
                        ->setId($queriesContentId)
                        ->active(false)),
                $this->editorTabContent($rqQuery, true)
            )->setId($tabsContentId)
        );
    }
}
