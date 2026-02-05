<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\PageComponent as BaseComponent;
use Jaxon\Attributes\Attribute\Databag;

#[Databag('dbadmin')]
abstract class PageComponent extends BaseComponent
{
    use ComponentTrait;
    use TabItemTrait;

    /**
     * Render the page and pagination components
     *
     * @param int $pageNumber
     *
     * @return void
     */
    public function page(int $pageNumber = 0): void
    {
        // Get the paginator. This will also set the current page number value.
        $this->paginate($this->rq()->page(), $pageNumber);
    }
}
