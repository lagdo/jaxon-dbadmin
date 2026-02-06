<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

#[Before('setDefaultSelectOptions')]
#[Databag('dbadmin.select')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;
    use SelectBagTrait;

    /**
     * The constructor
     *
     * @param SelectUiBuilder   $selectUi   The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->getSelectBag('options', [])['limit'] ?? 50;
    }
}
