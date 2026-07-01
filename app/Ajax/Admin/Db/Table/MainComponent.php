<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class MainComponent extends Component
{
    use ComponentTrait;
    use ContentTrait;

    /**
     * @param TableUiBuilder $tableUi   The HTML UI builder
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}
}
