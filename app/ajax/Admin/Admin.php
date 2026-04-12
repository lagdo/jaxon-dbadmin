<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Exclude;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Server;
use Lagdo\DbAdmin\Db\Service\Admin\Preference;
use Lagdo\DbAdmin\Ui\TabApp;

use function array_shift;
use function count;

#[Databag('dbadmin.tab')]
class Admin extends FuncComponent
{
    /**
     * @var Preference|null
     */
    protected Preference|null $preference;

    /**
     * Connect to a database server.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    #[Exclude]
    public function connect(string $server): void
    {
        $this->logger()->info('Connecting to server', ['server' => $server]);
        // Set the selected server
        $this->db()->selectDatabase($server);

        $this->cl(Server::class)->connect($server);
    }

    /**
     * Connect to a database server.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function server(string $server): void
    {
        $this->connect($server);

        if (!$this->bag('dbadmin')->get('tab.app')) {
            $this->bag('dbadmin')->set('tab.app', TabApp::current());
        }
        // Initially clear all the tabs.
        $this->setBag('dbadmin.tab', 'editor.names.sv', []);
        $this->setBag('dbadmin.tab', 'editor.names.db', []);
        $this->setBag('dbadmin.tab', 'editor.saved.sv', true);
        $this->setBag('dbadmin.tab', 'editor.saved.db', true);
    }

    /**
     * @param array $tab
     *
     * @return array
     */
    private function getDefaultServer(array $tab): array
    {
        return match(true) {
            isset($tab['server']) => [
                $tab['server'],
                $tab['title'] ?: $this->trans->lang('(No title)'),
            ],
            $this->config->hasOption('default') => [
                $this->config->getOption('default'),
                $this->trans->lang('(No title)'),
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
        return !$this->preference ? [] :
            array_filter($this->preference->getAppTabs(), fn(array $tab) =>
                $this->config->hasOption('servers.' . $tab['server']));
    }

    /**
     * Connect to a database server.
     *
     * @return void
     */
    #[Inject(attr: 'preference')]
    public function start(): void
    {
        // Toast library for the SQL editor.
        if ($this->config->hasOption('ui.toast.lib')) {
            $this->response()->jo('jaxon.dbadmin')
                ->setToastLib($this->config->getOption('ui.toast.lib'));
        }

        $tabs = $this->getSavedAppTabs();
        // Connect the first tab to the first saved of default database.
        [$server, $title] = $this->getDefaultServer($tabs[0] ?? []);
        if ($server !== '') {
            $this->setCurrentTitle($title);
            $this->response()->html(TabApp::titleId(), $title);
            // The first tab content is loaded.
            $this->server($server);
            // Updating the breadcrumbs after the request processing doesn't work here.
            // So we need to do it manually here.
            $this->showBreadcrumbs();
        }
        if (count($tabs) < 2) {
            return;
        }

        // Remove the first tab.
        array_shift($tabs);
        // Create the other saved tabs, but don't load the contents yet.
        foreach ($tabs as $tab) {
            $title = $tab['title'] ?: $this->trans()->lang('(No title)');
            $this->cl(TabFunc::class)->createTab($title);
            // Important to update the databag with the database opened in the tab here.
            $this->setCurrentDb([$tab['server'], $tab['database'], $tab['schema']]);
            $this->setCurrentTitle($tab['title'] ?: $this->trans->lang('(No title)'));
        }

        // This request can't actually load the tabs contents because the databags
        // values are not yet set. So it will just run another requests.
        $names = $this->bag('dbadmin.tab')->get('app.names', []);
        $this->response()->rq(Admin::class)->tabs($names);
    }

    /**
     * Load the saved tabs contents.
     *
     * @param array $names
     *
     * @return void
     */
    #[Inject(attr: 'preference')]
    public function tabs(array $names): void
    {
        $tabs = $this->getSavedAppTabs();
        // Remove the first tab.
        array_shift($tabs);
        $count = count($tabs);
        for ($index = 0; $index < $count; $index++) {
            // Load the tabs contents with an ajax request.
            $this->response()->jo('jaxon.dbadmin')->onAppTabClick($names[$index]);
            $this->response()->rq(Admin::class)->server($tabs[$index]['server']);
        }
    }
}
