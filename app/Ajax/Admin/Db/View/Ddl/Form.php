<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Component;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Table\ViewUiBuilder;

class Form extends Component
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
        $data = $this->get('data');
        return $this->viewUi->form($data['materialized'], $data['view'] ?? []);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server,] = $this->getCurrentDb();
        $driver = $this->config()->getServerDriver($server);
        $this->response()->jo('jaxon.dbadmin')
            ->createViewEditor($this->viewUi->queryFormId(), $driver);
    }

    /**
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function add(): void
    {
        $this->set('data', ['materialized' => $this->driver()->support('materializedview')]);
        $this->driver()->breadcrumbs(true)
            ->item($this->trans->lang('Views'))
            ->item($this->trans->lang('Create view'));

        $actions = [
            'back-views' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
        $this->render();
    }

    /**
     * @param string $view
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function edit(string $view): void
    {
        $this->set('data', $this->driver()->getView($view));
        $this->driver()->breadcrumbs(true)
            ->item($this->trans->lang('Views'))
            ->item("<i><b>$view</b></i>")
            ->item($this->trans->lang('Edit view'));

        $actions = [
            'select-view' => [
                'title' => $this->trans()->lang('Select'),
                'handler' => $this->rq(Select::class)->show($view),
            ],
            'show-view' => [
                'title' => $this->trans()->lang('Show view'),
                'handler' => $this->rq(View::class)->show($view),
            ],
            'drop-view' => [
                'title' => $this->trans()->lang('Drop view'),
                'handler' => $this->rq(ViewFunc::class)->drop($view)
                    ->confirm($this->trans->lang('Drop view %s?', $view)),
            ],
            'back-views' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $this->render();
    }
}
