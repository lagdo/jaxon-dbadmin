<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query\QueryTrait;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;

#[Databag('dbadmin.tab')]
class QueryEditor extends Component
{
    use ComponentDataTrait;
    use QueryTrait;

    /**
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

        // Set the current database, but do not update the databag.
        $this->driver()->setCurrentDbName('');
        $this->driver()->prepareQueryExec();
    }

    /**
     * @return void
     */
    private function showEditorTabs(): void
    {
        $this->cl(EditorFunc::class)->showTabs($this->get('query'));
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
        $this->set('query', $query);
        $this->render();
    }
}
