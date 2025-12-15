<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Table\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\SelectUiBuilder;

#[Before('setDefaultSelectOptions')]
#[Databag('dbadmin.select')]
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
