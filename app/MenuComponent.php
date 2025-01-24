<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\Component;
use Lagdo\DbAdmin\App\Ui\MenuBuilder;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;

abstract class MenuComponent extends Component
{
    /**
     * @var MenuBuilder
     */
    private $ui;

    /**
     * @var Translator
     */
    private $trans;

    /**
     * @var DbFacade
     */
    private $db;

    /**
     * @param MenuBuilder $ui
     * @param Translator $trans
     * @param DbFacade $db
     */
    public function __construct(MenuBuilder $ui, Translator $trans, DbFacade $db)
    {
        $this->ui = $ui;
        $this->trans = $trans;
        $this->db = $db;
    }

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
