<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\App\Page\Content;

/**
 * @before checkDatabaseAccess
 */
abstract class MainComponent extends Component
{
    use ComponentTrait;

    /**
     * @var string
     */
    protected $overrides = Content::class;
}
