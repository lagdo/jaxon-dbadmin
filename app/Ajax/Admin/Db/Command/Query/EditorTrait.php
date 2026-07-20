<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Service\Admin\AuditDatabase;

use function array_filter;
use function array_shift;
use function count;
use function in_array;

trait EditorTrait
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
    private string $queryClass;

    /**
     * @return void
     */
    abstract protected function setEditorPage(): void;

    /**
     * @return Tab
     */
    abstract protected function tab(): Tab;

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
        $schema = $this->tab()->editor()->onPage('db') ?
            $this->driver()->getSchemaColumns() : [];
        $this->response()->jo('jaxon.dbadmin')->createQueryEditor($containerId, $driver,
            $schema, $this->tab()->app()->current(), $this->tab()->editor()->page(),
            $this->tab()->editor()->current());
    }

    /**
     * @return void
     */
    #[Exclude]
    public function initTab(): void
    {
        // Always start with tab zero.
        $this->setEditorPage();
        $appTab = $this->tab()->app()->current();
        $editorTab = $this->tab()->editor()->zero();
        $this->bag('dbadmin.app')->set("tab.ed_$appTab", $editorTab);
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function addEditorTab(string $name): void
    {
        $appTab = $this->tab()->app()->current();
        $this->bag('dbadmin.app')->set("tab.ed_$appTab", $name);

        $navId = $this->queryUi->editorTabNavWrapperId();
        $nav = $this->queryUi->editorTabNavHtml();
        $contentId = $this->queryUi->editorTabContentWrapperId();

        $content = $this->queryUi->canSaveQuery($this->auditDb->canSaveQuery())
            ->editorTabContentHtml($this->rq($this->queryClass));
        $this->response()->jo('jaxon.dbadmin')->addTab($navId, $nav, $contentId, $content);

        $this->setupNewTab();
        // Activate the created tab.
        $titleId = $this->tab()->editor()->titleId();
        $this->response()->jo('jaxon.dbadmin')->activateTab($titleId);
    }

    /**
     * @return array
     */
    abstract protected function getSavedTabs(): array;

    /**
     * Recreate the saved tabs.
     *
     * @return bool
     */
    private function createSavedTabs(): bool
    {
        $savedId = $this->tab()->editor()->saved();
        if (!$this->getBag('dbadmin.tab', $savedId, true)) {
            return false;
        }

        $this->setBag('dbadmin.tab', $savedId, false);
        $savedTabs = $this->getSavedTabs();
        if (count($savedTabs) > 0) {
            // The first tab is already created. Just need to set the query text.
            $query = array_shift($savedTabs);
            $this->response()->jo('jaxon.dbadmin')->setQueryText($query);
            foreach ($savedTabs as $query) {
                $this->addTab();
                $this->response()->jo('jaxon.dbadmin')->setQueryText($query);
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    private function createTabs(): bool
    {
        // The saved tabs are fetched only on the first access to the query editor.
        if ($this->createSavedTabs()) {
            return true;
        }

        // Show the other opened tabs. The addEditorTab() function is used
        // here because the tabs are already saved in the databag.
        $bagNamesKey = $this->tab()->editor()->names();
        $names = $this->getBag('dbadmin.tab', $bagNamesKey, []);
        foreach ($names as $name) {
            $this->addEditorTab($name);
        }
        return count($names) > 0;
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

        $created = $this->createTabs();
        if($query !== '') {
            // Create a new tab for the query if other tabs were already created.
            if ($created) {
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
        $name = $this->tab()->editor()->newId();
        $this->addEditorTab($name);

        $bagNamesKey = $this->tab()->editor()->names();
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
        $appTab = $this->tab()->app()->current();
        $bagNamesKey = $this->tab()->editor()->names();
        return [
            $this->getBag('dbadmin.tab', $bagNamesKey, []),
            $this->bag('dbadmin.app')->get("tab.ed_$appTab", ''),
        ];
    }

    /**
     * @return void
     */
    public function cloneTab(): void
    {
        [$names, $currTab] = $this->currentTabs();
        if ($currTab !== $this->tab()->editor()->zero() && !in_array($currTab, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to clone.');
            return;
        }

        $this->addTab();

        // Copy the query text from the previous tab to the new tab.
        $nextTab = $this->tab()->app()->current();
        $this->response()->jo('jaxon.dbadmin')->copyQueryText($nextTab, $currTab);
    }

    /**
     * @return void
     */
    public function delTab(): void
    {
        [$names, $currTab] = $this->currentTabs();
        if ($currTab === $this->tab()->editor()->zero() || count($names) === 0) {
            $this->alert()->title('Error')->error('Cannot delete the current tab.');
            return;
        }
        if (!in_array($currTab, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to delete.');
            return;
        }

        // Delete the current tab. This script also activates the first tab.
        $this->response()->jo('jaxon.dbadmin')->delTab($this->tab()->editor()->titleId(),
            $this->tab()->editor()->wrapperId(), $this->tab()->editor()->zeroTitleId());
        $this->response()->jo('jaxon.dbadmin')->deleteQueryEditor($this->tab()->app()->current(),
            $this->tab()->editor()->current());

        // Update the databag contents.
        $this->setBag('dbadmin.tab', $this->tab()->editor()->names(),
            array_filter($names, fn(string $name) => $name !== $currTab));
    }
}
