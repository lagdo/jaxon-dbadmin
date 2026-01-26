<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Ui\Tab;

use function array_filter;
use function count;
use function in_array;
use function strlen;
use function trim;

#[Databag('dbadmin.tabs')]
class TabFunc extends FuncComponent
{
    /**
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function add(): void
    {
        // Get the last connected server.
        [$server, ] = $this->getCurrentDb();

        $name = Tab::newId();
        $this->bag('dbadmin.tab')->set('current', $name);
        $this->stash()->set('tab.current', $name);

        // The "names" array cannot be stored in the "dbadmin.tab" bacause
        // its values are overwritten each time the current tab is changed.
        $names = $this->bag('dbadmin.tabs')->get('names', []);
        $this->bag('dbadmin.tabs')->set('names', [...$names, $name]);

        $nav = $this->ui()->tabNavItemHtml($this->trans()->lang('(No title)'));
        $content = $this->ui()->tabContentItemHtml();
        $this->response()->jo('jaxon.dbadmin')->addTab($nav, $content, Tab::titleId());

        // Connect the new tab to the same last connected server.
        $this->cl(Admin::class)->server($server);
    }

    /**
     * @return void
     */
    public function del(): void
    {
        $names = $this->bag('dbadmin.tabs')->get('names', []);
        $current = $this->bag('dbadmin.tab')->get('current', '');
        if ($current === Tab::zero() || count($names) === 0) {
            $this->alert()->title('Error')->error('Cannot delete the current tab.');
            return;
        }
        if (!in_array($current, $names)) {
            $this->alert()->title('Error')->error('Cannot find the tab to delete.');
            return;
        }

        // Delete the current tab. This script also activates the first tab.
        $this->response()->jo('jaxon.dbadmin')
            ->deleteTab(Tab::titleId(), Tab::wrapperId(), Tab::zeroTitleId());

        // Update the databag contents.
        $this->bag('dbadmin.tabs')->set('names',
            array_filter($names, fn(string $name) => $name !== $current));
        // The js code also sets the current tab. But the new value set there is overwritten by
        // the one coming from this response. So we also need to set it here.
        $this->bag('dbadmin.tab')->set('current', Tab::zero());
    }

    /**
     * @return string
     */
    private function getCurrentTitle(): string
    {
        return $this->getBag('dbadmin', 'title', '');
    }

    /**
     * @param string $currentTitle
     *
     * @return void
     */
    private function setCurrentTitle(string $currentTitle): void
    {
        $this->setBag('dbadmin', 'title', $currentTitle);
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
        $this->response()->html(Tab::titleId(), $title);

        $this->modal()->hide();
    }
}
