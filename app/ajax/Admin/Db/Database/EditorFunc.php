<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query\EditorTrait;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
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
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param QueryUiBuilder $queryUi    The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected QueryUiBuilder $queryUi, protected Translator $trans)
    {}

    /**
     * @return void
     */
    protected function setEditorPage(): void
    {
        TabEditor::$page = 'db';
    }
}
