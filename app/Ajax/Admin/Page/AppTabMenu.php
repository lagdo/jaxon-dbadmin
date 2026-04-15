<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\TabFunc;
use Lagdo\DbAdmin\App\Ajax\Base\ComponentTrait;

#[Exclude]
class AppTabMenu extends Component
{
    use ComponentTrait;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $rqTab = rq(TabFunc::class);
        $menuEntries = [[
            'label' => '<i class="fa fa-plus"></i>',
            'handler' => $rqTab->add(),
        ], [
            'label' => $this->trans->lang('Title'),
            'handler' => $rqTab->editTitle(),
        ], [
            'label' => $this->trans->lang('Delete'),
            'handler' => $rqTab->del()
                ->confirm($this->trans->lang('Delete the current tab?')),
        ]];
        if ($this->config()->userPreferencesEnabled()) {
            $question = $this->trans->lang('Save the current tabs in your preferences?');
            $menuEntries[] =  [
                'label' => $this->trans->lang('Save tabs'),
                'handler' => $rqTab->saveAppTabs()->confirm($question),
            ];
        }

        return $this->ui()->tableMenu($menuEntries);
    }
}
