<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

use function intval;
use function trim;

trait QueryTrait
{
    /**
     * @var QueryUiBuilder
     */
    protected QueryUiBuilder $queryUi;

    /**
     * @var string
     */
    private string $database = '';

    /**
     * @var string
     */
    private string $editorClass;

    /**
     * @return string
     */
    public function html(): string
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);
        $this->db()->prepareCommand();

        return $this->queryUi->command($this->rq(), $this->rq($this->editorClass));
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

        if ($this->config()->hasAuditDatabase()) {
            if ($this->config()->historyEnabled()) {
                $this->cl(History::class)->render();
            }
            if ($this->config()->favoriteEnabled()) {
                $this->cl(Favorite::class)->render();
            }
        }

        // Show the SQL editor tabs.
        $this->showEditorTabs();
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param string $query
     * @param array $values
     *
     * @return void
     */
    public function exec(string $query, array $values): void
    {
        $query = trim($query);
        if(!$query)
        {
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $this->db()->prepareCommand();

        $limit = intval($values['limit'] ?? 0);
        $errorStops = $values['error_stops'] ?? false;
        $onlyErrors = $values['only_errors'] ?? false;
        $results = $this->db()->executeCommands($query, $limit, $errorStops, $onlyErrors);

        $this->cl(ResultSet::class)->renderResults($results);
    }
}
