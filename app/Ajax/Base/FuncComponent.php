<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Jaxon\App\FuncComponent as JaxonFuncComponent;
use Jaxon\Attributes\Attribute\Databag;

#[Databag('dbadmin')]
class FuncComponent extends JaxonFuncComponent
{
    use ComponentTrait;
}
