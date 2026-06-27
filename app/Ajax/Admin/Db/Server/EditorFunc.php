<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query\EditorTrait;
use Lagdo\DbAdmin\Support\Service\Admin\Preference;
use Lagdo\DbAdmin\App\Ui\Command\QueryUiBuilder;

#[Databag('dbadmin.tab')]
#[Before('setEditorPage')]
class EditorFunc extends FuncComponent
{
    use EditorTrait;

    /**
     * @param QueryUiBuilder $queryUi
     * @param Preference|null $preference
     */
    public function __construct(protected QueryUiBuilder $queryUi,
        protected Preference|null $preference)
    {}

    /**
     * @return void
     */
    protected function setEditorPage(): void
    {
        $this->tab()->editor()->setPage('sv');
        $this->queryClass = QueryEditor::class;
    }

    /**
     * @return array
     */
    protected function getSavedTabs(): array
    {
        [$server, ] = $this->getCurrentDb();
        return $this->preference?->getServerTabs($server) ?? [];
    }

    /**
     * @param array $tabs
     *
     * @return void
     */
    public function saveTabs(array $tabs): void
    {
        [$server, ] = $this->getCurrentDb();
        !$this->preference?->saveServerTabs($server, $tabs) ?
            $this->alert()->title('Error')
                ->error("Unable to save server tabs in user preferences.") :
            $this->alert()->title('Success')
                ->success("The server tabs are saved in user preferences.");
    }
}
