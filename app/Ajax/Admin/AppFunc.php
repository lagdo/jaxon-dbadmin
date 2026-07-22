<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\AppTabMenu;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Support\Service\Admin\Preference;

use function array_filter;
use function array_shift;
use function array_values;
use function count;
use function in_array;
use function strlen;
use function trim;

#[Databag('dbadmin.tab')]
class AppFunc extends FuncComponent
{
    /**
     * @param Preference|null $preference
     */
    public function __construct(private Preference|null $preference)
    {}

    /**
     * Connect to a database server, and execute the callbacks.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    private function server(string $server): void
    {
        $this->cl(DbFunc::class)->server($server);
        $this->showBreadcrumbs();
    }

    /**
     * @param array|null $tab
     *
     * @return array
     */
    private function getDefaultServer(array|null $tab): array
    {
        return match(true) {
            $tab !== null => [
                $tab['server'],
                $tab['title'] ?: $this->trans()->lang('(No title)'),
            ],
            $this->config()->hasOption('default') => [
                $this->config()->getOption('default'),
                $this->trans()->lang('(No title)'),
            ],
            default => ['', ''],
        };
    }

    /**
     * @return array
     */
    private function getSavedAppTabs(): array
    {
        // The preference service can be null.
        return array_filter($this->preference?->getAppTabs() ?? [],
            fn(array $tab) => $this->config()->hasOption('servers.' . $tab['server']));
    }

    /**
     * @return void
     */
    public function start(): void
    {
        // Toast library for the SQL editor.
        if ($this->config()->hasOption('ui.toast.lib')) {
            $this->response()->jo('jaxon.dbadmin')
                ->setToastLib($this->config()->getOption('ui.toast.lib'));
        }

        // Show the app tab menu.
        $this->cl(AppTabMenu::class)->render();

        $tabs = array_values($this->getSavedAppTabs());
        // Connect the first tab to the first saved of default database.
        [$server, $title] = $this->getDefaultServer($tabs[0] ?? null);
        if ($server !== '') {
            $this->setCurrentTitle($title);
            $this->response()->html($this->tab()->app()->titleId(), $title);
            // The first tab content is loaded.
            $this->server($server);
        }

        if (count($tabs) > 1) {
            // This must be a synchronous call. See the dbadmin.php config file.
            $this->response()->rq(AppFunc::class)->addSavedTabs();
        }
    }

    /**
     * @param string $title
     *
     * @return void
     */
    private function createTab(string $title): void
    {
        $appTab = $this->tab()->app();

        $name = $appTab->newId();
        $this->bag('dbadmin.app')->set('tab.app', $name);

        $names = $this->bag('dbadmin.app')->get('tabs', []);
        $this->bag('dbadmin.app')->set('tabs', [...$names, $name]);
        $this->setBag('dbadmin.tab', 'editor.names.sv', []);
        $this->setBag('dbadmin.tab', 'editor.names.db', []);

        $nav = $this->ui()->tabNavItemHtml($title);
        $content = $this->ui()->tabContentItemHtml();
        $this->response()->jo('jaxon.dbadmin')->addTab('dbadmin-server-tab-nav',
            $nav, 'dbadmin-server-tab-content', $content, $appTab->titleId());
    }

    /**
     * Load the saved tabs contents.
     *
     * @return void
     */
    public function addSavedTabs(): void
    {
        $tabs = $this->getSavedAppTabs();
        // Remove the first tab.
        array_shift($tabs);

        // Create the other saved tabs.
        foreach ($tabs as $name => $_) {
            // This must be a synchronous call. See the dbadmin.php config file.
            $this->response()->rq(AppFunc::class)->addSavedTab($name);
        }
    }

    /**
     * @param string $server
     *
     * @return string
     */
    private function getTabTitle(string $server): string
    {
        $serverNames = $this->config()->getServerNames();;
        return $serverNames[$server] ?? $this->trans()->lang('(No title)');
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function addSavedTab(string $name = ''): void
    {
        $tab = $this->getSavedAppTabs()[$name] ?? null;
        if ($tab === null || !isset($tab['server'])) {
            return;
        }

        $server = $tab['server'];
        $title = $tab['title'] ?: $this->getTabTitle($server);
        // Important to update the databag with the database opened in the tab here.
        $this->createTab($title);
        $this->setCurrentDb([$server, $tab['database'], $tab['schema']]);
        $this->setCurrentTitle($title);

        // Connect the new tab to the provided server.
        if ($server !== '') {
            $this->server($server);
        }

        // Back to the first tab.
        $appTab = $this->tab()->app();
        $this->response()->jo('jaxon.dbadmin')->activateTab($appTab->zeroTitleId());
        $this->bag('dbadmin.app')->set('tab.app', $appTab->zero());
    }

    /**
     * @return void
     */
    public function addTab(): void
    {
        // Get the last connected server. It's important to get this before
        // because the createTab() method modifies the databag content.
        $server = $this->getCurrentDb()[0] ?? '';
        $this->createTab($this->getTabTitle($server));

        // Connect the new tab to the provided server.
        if ($server !== '') {
            $this->server($server);
        }
    }

    /**
     * @return void
     */
    public function delTab(): void
    {
        $appTab = $this->tab()->app();

        $names = $this->bag('dbadmin.app')->get('tabs', []);
        $current = $this->bag('dbadmin.app')->get('tab.app', '');
        if ($current === $appTab->zero() || count($names) === 0) {
            $this->alert()->title('Error')->error('Cannot delete the current tab.');
            return;
        }
        if (!in_array($current, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to delete.');
            return;
        }

        // Delete the current tab. This script also activates the first tab.
        $this->response()->jo('jaxon.dbadmin')->delTab($appTab->titleId(),
            $appTab->wrapperId(), $appTab->zeroTitleId());
        // Delete the query editors created in the tab;
        $this->response()->jo('jaxon.dbadmin')->delAppEditors($appTab->current());

        // Update the databag contents.
        $this->bag('dbadmin.app')->set('tabs',
            array_filter($names, fn(string $name) => $name !== $current));
        $this->unsetCurrentDb();
        $this->unsetBag('dbadmin.tab', 'editor.names.sv');
        $this->unsetBag('dbadmin.tab', 'editor.names.db');

        // Set the first tab as the current.
        $this->bag('dbadmin.app')->set('tab.app', $appTab->zero());
    }

    /**
     * @return void
     */
    public function editTabTitle(): void
    {
        $title = $this->trans()->lang('Edit tab title');
        $content = $this->ui()->editTabTitle($this->getCurrentTitle());
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveTabTitle($this->ui()->tabTitleFormValues()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    public function saveTabTitle(array $formValues): void
    {
        $title = trim($formValues['title'] ?? '');
        if ($title === '' || strlen($title) > 20) {
            $this->alert()->title('Error')->error("The title '$title' is incorrect.");
            return;
        }

        // Change the tab title, and save the title in the databag.
        $this->setCurrentTitle($title);
        $this->response()->html($this->tab()->app()->titleId(), $title);

        $this->modal()->hide();
    }

    /**
     * @return void
     */
    public function saveAppTabs(): void
    {
        $dbadminBag = $this->bag('dbadmin');
        $titles = $dbadminBag->get('title', []);
        $tabs = [];
        foreach ($dbadminBag->get('db', []) as $name => $tab) {
            $tabs[$name] = [
                'server' => $tab[0],
                'database' => $tab[1] ?? '',
                'schema' => $tab[2] ?? '',
                'title' => $titles[$name] ?? '',
            ];
        }

        !$this->preference->saveAppTabs($tabs) ?
            $this->alert()->title('Error')
                ->error("Unable to save tabs in user preferences.") :
            $this->alert()->title('Success')
                ->success("The tabs are saved in user preferences.");
    }
}
