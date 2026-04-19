<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Jaxon\Attributes\Attribute\After;

/**
 * Features for page content components with three parts: header, content and footer.
 */
#[After('fixPageContentHeight')]
trait PageContentFixHeightTrait
{
    /**
     * @return string
     */
    protected function fixPageContentHeight(): void
    {
        $tabId = $this->tab()->app()->id('dbadmin-app-tab-content');
        $wrapper = 'jaxon-dbadmin-column-flexible';
        $content = 'jaxon-dbadmin-scrollable-content';
        $this->response()->jo('jaxon.dbadmin')->fixPageContentHeight($tabId, $wrapper, $content);
    }
}
