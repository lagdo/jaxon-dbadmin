<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Page;

use Jaxon\App\Component;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\AppFunc;
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
        $rqApp = rq(AppFunc::class);
        $menuEntries = [[
            'label' => '<i class="fa fa-plus"></i>',
            'handler' => $rqApp->addTab(),
        ], [
            'label' => $this->trans()->lang('Title'),
            'handler' => $rqApp->editTabTitle(),
        ], [
            'label' => $this->trans()->lang('Delete'),
            'handler' => $rqApp->delTab()
                ->confirm($this->trans()->lang('Delete the current tab?')),
        ]];
        if ($this->config()->showQueryPreferences()) {
            $question = $this->trans()->lang('Save the current tabs in your preferences?');
            $menuEntries[] =  [
                'label' => $this->trans()->lang('Save tabs'),
                'handler' => $rqApp->saveAppTabs()->confirm($question),
            ];
        }

        return $this->ui()->buttonMenu($menuEntries);
    }
}
