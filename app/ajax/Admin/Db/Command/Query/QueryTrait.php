<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\DbAdmin\Ui\TabEditor;

use function array_filter;
use function count;
use function in_array;
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
    private string $query = '';

    /**
     * @return string
     */
    abstract private function queryPage(): string;

    /**
     * @return string
     */
    public function html(): string
    {
        // Always start with tab zero.
        TabEditor::$page = $this->queryPage();
        $this->bag('dbadmin')->set('tab.editor', TabEditor::zero());

        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);

        $this->db()->prepareCommand();

        return $this->queryUi->command($this->rq());
    }

    /**
     * @return void
     */
    private function setupNewTab(): void
    {
        [$server,] = $this->getCurrentDb();
        $driver = $this->config()->getServerDriver($server);
        // Create the SQL editor in the new tab.
        $this->response()->jo('jaxon.dbadmin')
            ->createQueryEditor($this->queryUi->commandEditorId(), $driver,
                TabApp::current(), TabEditor::current());
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function addEditorTab(string $name): void
    {
        $this->bag('dbadmin')->set('tab.editor', $name);

        $navId = $this->queryUi->editorTabNavWrapperId();
        $nav = $this->queryUi->editorTabNavHtml();
        $contentId = $this->queryUi->editorTabContentWrapperId();
        $content = $this->queryUi->editorTabContentHtml($this->rq());
        $this->response()->jo('jaxon.dbadmin')->addTab($navId, $nav, $contentId, $content);

        $this->setupNewTab();
    }

    /**
     * @return string
     */
    private function bagNamesKey(): string
    {
        return 'editor.names.' . TabEditor::$page;
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        // Create the SQL editor for the first tab.
        $this->setupNewTab();
        if($this->query !== '')
        {
            $this->response()->jo('jaxon.dbadmin')->setSqlQuery($this->query);
        }

        // Show the other opened tabs.
        $names = $this->getBag('dbadmin.tab', $this->bagNamesKey(), []);
        foreach ($names as $name) {
            $this->addEditorTab($name);
        }
        // Set the tab zero as active.
        $this->bag('dbadmin')->set('tab.editor', TabEditor::zero());

        if (!$this->config()->hasAuditDatabase()) {
            return;
        }
        if ($this->config()->historyEnabled()) {
            $this->cl(History::class)->render();
        }
        if ($this->config()->favoriteEnabled()) {
            $this->cl(Favorite::class)->render();
        }
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

        $this->cl(Results::class)->renderResults($results);
    }

    /**
     * @return void
     */
    public function addTab(): void
    {
        TabEditor::$page = $this->queryPage();
        $bagNamesKey = $this->bagNamesKey();

        $name = TabEditor::newId();
        $this->addEditorTab($name);
        // The addEditorTab() function dos not activate the created tab.
        $this->response()->jo('jaxon.dbadmin')->activateTab(TabEditor::titleId());

        $names = $this->getBag('dbadmin.tab', $bagNamesKey, []);
        $this->setBag('dbadmin.tab', $bagNamesKey, [...$names, $name]);

        // Create an instance of the SQL editor for the new tab.
        $this->setupNewTab();
    }

    /**
     * @return void
     */
    public function delTab(): void
    {
        TabEditor::$page = $this->queryPage();
        $bagNamesKey = $this->bagNamesKey();

        $names = $this->getBag('dbadmin.tab', $bagNamesKey, []);
        $current = $this->bag('dbadmin')->get('tab.editor', '');
        if ($current === TabEditor::zero() || count($names) === 0) {
            $this->alert()->title('Error')->error('Cannot delete the current tab.');
            return;
        }
        if (!in_array($current, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to delete.');
            return;
        }

        // Delete the current tab. This script also activates the first tab.
        $this->response()->jo('jaxon.dbadmin')
            ->delTab(TabEditor::titleId(), TabEditor::wrapperId(), TabEditor::zeroTitleId());
        $this->response()->jo('jaxon.dbadmin')
            ->deleteQueryEditor(TabApp::current(), TabEditor::current());

        // Update the databag contents.
        $this->setBag('dbadmin.tab', $bagNamesKey,
            array_filter($names, fn(string $name) => $name !== $current));
    }
}
