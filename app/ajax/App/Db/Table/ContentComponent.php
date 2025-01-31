<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\App\Page\Content;

abstract class ContentComponent extends Component
{
    /**
     * @var string
     */
    protected $overrides = Content::class;
}
