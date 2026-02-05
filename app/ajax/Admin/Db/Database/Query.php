<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

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
        $this->activateDatabaseCommandMenu('database-query');

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
     * Show the SQL command form for a database
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function database(string $query = ''): void
    {
        // The request might come from a modal dialog.
        $this->modal()->hide();

        [, $this->database] = $this->getCurrentDb();
        $this->query = $query;
        $this->render();
    }
}
