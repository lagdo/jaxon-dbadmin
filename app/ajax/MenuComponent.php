<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\MenuBuilder;

abstract class MenuComponent extends Component
{
    /**
     * @param MenuBuilder $ui
     * @param Translator $trans
     * @param DbFacade $db
     */
    public function __construct(private MenuBuilder $ui, private Translator $trans, private DbFacade $db)
    {}

    /**
     * @return DbFacade
     */
    protected function db(): DbFacade
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
