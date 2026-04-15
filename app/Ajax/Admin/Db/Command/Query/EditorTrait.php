<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\App\Ui\TabApp;
use Lagdo\DbAdmin\App\Ui\TabEditor;

use function array_filter;
use function count;
use function in_array;

trait EditorTrait
{
    /**
     * @var QueryUiBuilder
     */
    protected QueryUiBuilder $queryUi;

    /**
     * @var string
     */
    private string $queryClass;

    /**
     * @return void
     */
    abstract protected function setEditorPage(): void;

    /**
     * @return void
     */
    #[Exclude]
    public function initTab(): void
    {
        // Always start with tab zero.
        $this->setEditorPage();
        $this->bag('dbadmin')->set('tab.editor', TabEditor::zero());
    }

    /**
     * @return void
     */
    private function setupNewTab(): void
    {
        [$server,] = $this->getCurrentDb();
        $driver = $this->config()->getServerDriver($server);
        // Create the SQL editor in the new tab.
        $containerId = $this->queryUi->commandEditorId();
        // The query completion is enabled only in the database editor.
        $schema = TabEditor::$page === 'db' ? $this->db()->getSchemaColumns() : [];
        $this->response()->jo('jaxon.dbadmin')->createQueryEditor($containerId, $driver,
            $schema, TabApp::current(), TabEditor::$page, TabEditor::current());
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

        $content = $this->queryUi->canSaveQuery($this->config()->canSaveQuery())
            ->editorTabContentHtml($this->rq($this->queryClass));

        $this->response()->jo('jaxon.dbadmin')->addTab($navId, $nav, $contentId, $content);

        $this->setupNewTab();
    }

    /**
     * @return array
     */
    abstract protected function getSavedTabs(): array;

    /**
     * @return int
     */
    private function _showTabs(): int
    {
        // The saved tabs are fetched only on the first access to the query editor.
        if ($this->getBag('dbadmin.tab', TabEditor::saved(), true)) {
            $this->setBag('dbadmin.tab', TabEditor::saved(), false);

            $savedTabs = $this->getSavedTabs();
            if (($count = count($savedTabs)) > 0) {
                // Recreate the saved tabs.
                $firstTab = true;
                foreach ($savedTabs as $query) {
                    // The first tab is already created. Just need to set the query text.
                    if (!$firstTab) {
                        $this->addTab();
                    }
                    $this->response()->jo('jaxon.dbadmin')->setQueryText($query);
                    $firstTab = false;
                }
                return $count;
            }
        }

        // Show the other opened tabs. The addEditorTab() function is used
        // here because the tabs are already saved in the databag.
        $names = $this->getBag('dbadmin.tab', TabEditor::names(), []);
        foreach ($names as $name) {
            $this->addEditorTab($name);
        }
        return count($names);
    }

    /**
     * @param string $query
     *
     * @return void
     */
    #[Exclude]
    public function showTabs(string $query): void
    {
        // Create the SQL editor for the first tab.
        $this->setupNewTab();

        $count = $this->_showTabs();
        if($query !== '')
        {
            // Create a new tab for the query if other tabs ware already created.
            if ($count > 0) {
                $this->addTab();
            }
            $this->response()->jo('jaxon.dbadmin')->setQueryText($query);
        }
    }

    /**
     * @return void
     */
    public function addTab(): void
    {
        $name = TabEditor::newId();
        $this->addEditorTab($name);
        // The addEditorTab() function dos not activate the created tab.
        $this->response()->jo('jaxon.dbadmin')->activateTab(TabEditor::titleId());

        $bagNamesKey = TabEditor::names();
        $names = $this->getBag('dbadmin.tab', $bagNamesKey, []);
        $this->setBag('dbadmin.tab', $bagNamesKey, [...$names, $name]);

        // Create an instance of the SQL editor for the new tab.
        $this->setupNewTab();
    }

    /**
     * @return array
     */
    private function currentTabs(): array
    {
        $bagNamesKey = TabEditor::names();
        return [
            $this->getBag('dbadmin.tab', $bagNamesKey, []),
            $this->bag('dbadmin')->get('tab.editor', ''),
        ];
    }

    /**
     * @return void
     */
    public function cloneTab(): void
    {
        [$names, $current] = $this->currentTabs();
        if ($current !== TabEditor::zero() && !in_array($current, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to clone.');
            return;
        }

        $this->addTab();

        // Copy the query text from the previous current tab to the new tab.
        $this->response()->jo('jaxon.dbadmin')->copyQueryText(TabApp::current(), $current);
    }

    /**
     * @return void
     */
    public function delTab(): void
    {
        [$names, $current] = $this->currentTabs();
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
        $this->setBag('dbadmin.tab', TabEditor::names(), array_filter($names,
            fn(string $name) => $name !== $current));
    }
}
