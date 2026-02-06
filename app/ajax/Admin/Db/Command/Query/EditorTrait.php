<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\DbAdmin\Ui\TabEditor;

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
        $this->response()->jo('jaxon.dbadmin')
            ->createQueryEditor($this->queryUi->commandEditorId(),
                $driver, TabApp::current(), TabEditor::current());
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
     * @param string $query
     *
     * @return void
     */
    #[Exclude]
    public function showTabs(string $query): void
    {
        // Create the SQL editor for the first tab.
        $this->setupNewTab();
        if($query !== '')
        {
            $this->response()->jo('jaxon.dbadmin')->setQueryText($query);
        }

        // Show the other opened tabs.
        $names = $this->getBag('dbadmin.tab', TabEditor::names(), []);
        foreach ($names as $name) {
            $this->addEditorTab($name);
        }
        // Reset the tab zero as active. The addEditorTab() function changes it.
        $this->bag('dbadmin')->set('tab.editor', TabEditor::zero());
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
