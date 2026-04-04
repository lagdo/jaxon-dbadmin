<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Lagdo\DbAdmin\Db\Driver\DbProxy;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\MenuBuilder;

abstract class MenuComponent extends Component
{
    use ComponentDataTrait;
    use TabItemTrait;

    /**
     * @param MenuBuilder $ui
     * @param Translator $trans
     * @param DbProxy $db
     */
    public function __construct(private MenuBuilder $ui,
        private Translator $trans, private DbProxy $db)
    {}

    /**
     * @return DbProxy
     */
    protected function db(): DbProxy
    {
        return $this->db;
    }

    /**
     * @return MenuBuilder
     */
    protected function ui(): MenuBuilder
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }
}
