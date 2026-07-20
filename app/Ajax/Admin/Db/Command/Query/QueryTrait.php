<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Service\Admin\AuditDatabase;

use function intval;
use function trim;

trait QueryTrait
{
    /**
     * @var AuditDatabase
     */
    #[Inject]
    private AuditDatabase $auditDb;

    /**
     * @var QueryUiBuilder
     */
    protected QueryUiBuilder $queryUi;

    /**
     * @var string
     */
    private string $editorClass;

    /**
     * @return string
     */
    protected function content(): string
    {
        return $this->queryUi->canShowQuery($this->auditDb->canShowQuery())
            ->command($this->rq(), $this->rq($this->editorClass));
    }

    /**
     * @return void
     */
    abstract private function showEditorTabs(): void;

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $classToRender = match(true) {
            !$this->config()->hasQueryDatabaseOptions() => null,
            $this->config()->queryHistoryEnabled() => History::class,
            $this->config()->queryFavoriteEnabled() => Favorite::class,
            default => null,
        };
        if ($classToRender !== null) {
            $this->cl($classToRender)->render();
        }

        // Show the SQL editor tabs.
        $this->showEditorTabs();
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param string $queryText
     * @param array $values
     *
     * @return void
     */
    public function exec(string $queryText, array $values): void
    {
        $queryText = trim($queryText);
        if(!$queryText)
        {
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $this->driver()->prepareQueryExec();

        $options = new QueryOptions($values['error_stops'] ?? false,
            $values['only_errors'] ?? false, intval($values['limit'] ?? 0));
        $result = $this->driver()->executeQueriesInText($queryText, $options);

        $this->cl(QueryResult::class)->set('result', $result)->render();
    }
}
