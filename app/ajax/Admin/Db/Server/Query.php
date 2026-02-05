<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query\QueryTrait;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

#[Databag('dbadmin.tab')]
class Query extends Component
{
    use QueryTrait;

    /**
     * @var string
     */
    private string $query = '';

    /**
     * The constructor
     *
     * @param QueryUiBuilder $queryUi    The HTML UI builder
     */
    public function __construct(protected QueryUiBuilder $queryUi)
    {}

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateServerCommandMenu('server-query');

        $this->editorClass = EditorFunc::class;
        $this->cl(EditorFunc::class)->initTab();
    }

    /**
     * @return void
     */
    private function showEditorTabs(): void
    {
        $this->cl(EditorFunc::class)->showTabs($this->query);
    }

    /**
     * Show the SQL query form for a server
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function server(string $query = ''): void
    {
        $this->query = $query;
        $this->render();
    }
}
