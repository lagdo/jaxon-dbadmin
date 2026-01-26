<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Ui\Tab;

use function strlen;
use function trim;
use function uniqid;

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

        $name = 'app-tab-' . uniqid();
        $this->bag('dbadmin.tab')->set('current', $name);
        $this->stash()->set('tab.current', $name);
        $this->setupComponent();

        $nav = $this->ui()->tabNavItemHtml($this->trans()->lang('(No title)'));
        $content = $this->ui()->tabContentItemHtml();
        $this->response()->jo('jaxon.dbadmin')->addTab($nav, $content);

        // Connect the new tab to the same last connected server.
        $this->cl(Admin::class)->server($server);
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
