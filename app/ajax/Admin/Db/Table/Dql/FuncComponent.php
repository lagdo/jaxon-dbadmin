<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

#[Databag('dbadmin.select')]
#[Before('setDefaultSelectOptions')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbFacade      $db         The facade to database functions
     * @param SelectUiBuilder $selectUi The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected DbFacade $db,
        protected SelectUiBuilder $selectUi, protected Translator $trans)
    {}
}
