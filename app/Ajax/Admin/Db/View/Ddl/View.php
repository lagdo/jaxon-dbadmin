<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Component;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Table\ViewUiBuilder;

use function is_array;

class View extends Component
{
    use ContentTrait;

    /**
     * @param ViewUiBuilder  $viewUi     The HTML UI builder
     */
    public function __construct(protected ViewUiBuilder $viewUi)
    {}

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        return $this->get('content');
    }

    /**
     * Display the content of a tab
     *
     * @param array  $viewData  The data to be displayed in the view
     * @param string $tabId     The tab container id
     *
     * @return void
     */
    protected function showTab(array $viewData, string $tabId): void
    {
        $this->response()->html($tabId, $this->viewUi->pageContent($viewData));
    }

    /**
     * Show detailed info of a given view
     *
     * @param string $view        The view name
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(string $view): void
    {
        $viewInfo = $this->driver()->getViewInfo($view);

        $actions = [
            'select-view' => [
                'title' => $this->trans()->lang('Select'),
                'handler' => $this->rq(Select::class)->show($view),
            ],
            'edit-view' => [
                'title' => $this->trans()->lang('Edit view'),
                'handler' => $this->rq(Form::class)->edit($view),
            ],
            'drop-view' => [
                'title' => $this->trans()->lang('Drop view'),
                'handler' => $this->rq(ViewFunc::class)->drop($view)
                    ->confirm($this->trans()->lang('Drop view %s?', $view)),
            ],
            'back-views' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $content = $this->viewUi->mainDbTable($viewInfo['tabs']);
        $this->set('content', $content)->render();

        // Show columns
        $columns = $this->driver()->getViewColumns($view);
        $this->showTab($columns, $this->tabId('tab-content-columns'));

        // Show triggers
        $triggers = $this->driver()->getViewTriggers($view);
        if(is_array($triggers))
        {
            $this->showTab($triggers, $this->tabId('tab-content-triggers'));
        }
    }
}
