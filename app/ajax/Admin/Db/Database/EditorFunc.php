<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query\EditorTrait;
use Lagdo\DbAdmin\Db\Service\Admin\Preference;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\Ui\TabEditor;

#[Databag('dbadmin.tab')]
#[Before('setEditorPage')]
class EditorFunc extends FuncComponent
{
    use EditorTrait;

    /**
     * The constructor
     *
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
        TabEditor::$page = 'db';
        $this->queryClass = Query::class;
    }

    /**
     * @return array
     */
    protected function getSavedTabs(): array
    {
        [$server, $database] = $this->getCurrentDb();
        return $this->preference?->getDatabaseTabs($server, $database) ?? [];
    }

    /**
     * @param array $tabs
     *
     * @return void
     */
    public function saveTabs(array $tabs): void
    {
        [$server, $database] = $this->getCurrentDb();
        !$this->preference?->saveDatabaseTabs($server, $database, $tabs) ?
            $this->alert()->title('Error')
                ->error("Unable to save database tabs in user preferences.") :
            $this->alert()->title('Success')
                ->success("The database tabs are saved in user preferences.");
    }
}
