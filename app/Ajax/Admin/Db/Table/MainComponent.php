<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Base\PageContentTrait;
use Lagdo\DbAdmin\App\Ui\Table\TableUiBuilder;

#[Before('checkDatabaseAccess')]
#[Databag('dbadmin.table')]
abstract class MainComponent extends Component
{
    use ComponentTrait;
    use PageContentTrait;

    /**
     * @var string
     */
    protected string $overrides = Content::class;

    /**
     * @param TableUiBuilder $tableUi   The HTML UI builder
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}
}
