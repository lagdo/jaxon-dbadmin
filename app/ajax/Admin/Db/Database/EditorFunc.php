<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query\EditorTrait;
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
     * @param QueryUiBuilder $queryUi    The HTML UI builder
     */
    public function __construct(protected QueryUiBuilder $queryUi)
    {}

    /**
     * @return void
     */
    protected function setEditorPage(): void
    {
        TabEditor::$page = 'db';
        $this->queryClass = Query::class;
    }
}
