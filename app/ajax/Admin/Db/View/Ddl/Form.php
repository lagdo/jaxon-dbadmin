<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Component;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ViewUiBuilder;

class Form extends Component
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

    /**
     * @var string
     */
    private $queryId = 'dbadmin-views-edit-view';

    /**
     * @var array
     */
    private $data = [];

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param ViewUiBuilder  $viewUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected ViewUiBuilder $viewUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->viewUi->form($this->queryId,
            $this->data['materialized'], $this->data['view'] ?? []);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server,] = $this->bag('dbadmin')->get('db');
        $this->response()->jo('jaxon.dbadmin')->createSqlQueryEditor($this->queryId,
            $this->package->getServerDriver($server));
    }

    /**
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function add(): void
    {
        $this->data = ['materialized' => $this->db()->support('materializedview')];
        $this->db()->breadcrumbs(true)
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
        $this->data = $this->db()->getView($view);
        $this->db()->breadcrumbs(true)
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
                'handler' => $this->rq()->drop($view)->confirm("Drop view $view?"),
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
