<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;
use Lagdo\DbAdmin\Ui\UiBuilder;

#[Before('setDefaultSelectOptions')]
#[Databag('dbadmin.select')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbFacade          $db         The facade to database functions
     * @param UiBuilder         $ui         The HTML UI builder
     * @param SelectUiBuilder   $selectUi   The HTML UI builder
     * @param Translator        $trans
     */
    public function __construct(protected DbFacade $db, protected UiBuilder $ui,
        protected SelectUiBuilder $selectUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        $options = $this->bag('dbadmin.select')->get($this->tabKey('options'), []);
        return $options['limit'] ?? 50;
    }
}
