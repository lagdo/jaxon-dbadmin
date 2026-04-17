<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Exclude;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Support\Service\Admin\Preference;

use function array_filter;
use function count;
use function in_array;
use function strlen;
use function trim;

#[Databag('dbadmin.tab')]
class TabFunc extends FuncComponent
{
    /**
     * @var Preference
     */
    protected Preference $preference;

    /**
     * @return void
     */
    #[Exclude]
    public function createTab(string $title): void
    {
        $name = $this->tab()->app()->newId();
        $this->bag('dbadmin')->set('tab.app', $name);

        $names = $this->bag('dbadmin.tab')->get('app.names', []);
        $this->bag('dbadmin.tab')->set('app.names', [...$names, $name]);
        $this->setBag('dbadmin.tab', 'editor.names.sv', []);
        $this->setBag('dbadmin.tab', 'editor.names.db', []);

        $nav = $this->ui()->tabNavItemHtml($title);
        $content = $this->ui()->tabContentItemHtml();
        $this->response()->jo('jaxon.dbadmin')->addTab('dbadmin-server-tab-nav',
            $nav, 'dbadmin-server-tab-content', $content, $this->tab()->app()->titleId());
    }

    /**
     * @return void
     */
    public function add(): void
    {
        // Get the last connected server.
        $server = $this->getCurrentDb()[0] ?? '';
        $title = $this->trans()->lang('(No title)');

        $this->createTab($title);

        // Connect the new tab to the provided server.
        if ($server !== '') {
            $this->cl(Admin::class)->connect($server);
            $this->showBreadcrumbs();
        }
    }

    /**
     * @return void
     */
    public function del(): void
    {
        $names = $this->bag('dbadmin.tab')->get('app.names', []);
        $current = $this->bag('dbadmin')->get('tab.app', '');
        if ($current === $this->tab()->app()->zero() || count($names) === 0) {
            $this->alert()->title('Error')->error('Cannot delete the current tab.');
            return;
        }
        if (!in_array($current, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to delete.');
            return;
        }

        // Delete the current tab. This script also activates the first tab.
        $this->response()->jo('jaxon.dbadmin')
            ->delTab($this->tab()->app()->titleId(), $this->tab()->app()->wrapperId(), $this->tab()->app()->zeroTitleId());
        // Delete the query editors created in the tab;
        $this->response()->jo('jaxon.dbadmin')->delAppEditors($this->tab()->app()->current());

        // Update the databag contents.
        $this->bag('dbadmin.tab')->set('app.names',
            array_filter($names, fn(string $name) => $name !== $current));
        $this->unsetCurrentDb();
        $this->unsetBag('dbadmin.tab', 'editor.names.sv');
        $this->unsetBag('dbadmin.tab', 'editor.names.db');

        // Set the first tab as the current.
        $this->bag('dbadmin')->set('tab.app', $this->tab()->app()->zero());
    }

    /**
     * @return void
     */
    public function editTitle(): void
    {
        $title = $this->trans->lang('Edit tab title');
        $content = $this->ui->editTabTitle($this->getCurrentTitle());
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveTitle($this->ui->tabTitleFormValues()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param array $formValues
     *
     * @return void
     */
    public function saveTitle(array $formValues): void
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
    #[Inject(attr: 'preference')]
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
