<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class MainComponent extends Component
{
    use ComponentTrait;

    /**
     * @var string
     */
    protected string $overrides = Content::class;

    /**
     * The constructor
     *
     * @param TableUiBuilder $tableUi   The HTML UI builder
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}
}
