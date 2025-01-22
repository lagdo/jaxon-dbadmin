<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Lagdo\DbAdmin\App\Ajax\Page\Content;

abstract class ContentComponent extends Component
{
    /**
     * @var string
     */
    protected $overrides = Content::class;
}
