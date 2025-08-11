<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Component as BaseComponent;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Table\SelectUiBuilder;

/**
 * @databag dbadmin.select
 * @before setDefaultSelectOptions
 */
abstract class Component extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbFacade      $db         The facade to database functions
     * @param SelectUiBuilder $html       The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected DbFacade $db,
        protected SelectUiBuilder $html, protected Translator $trans)
    {}
}
