<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Table\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Table\SelectUiBuilder;

/**
 * @databag dbadmin.select
 * @before setDefaultSelectOptions
 */
abstract class PageComponent extends BaseComponent
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

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        $options = $this->bag('dbadmin.select')->get('options', []);
        return $options['limit'] ?? 50;
    }
}
