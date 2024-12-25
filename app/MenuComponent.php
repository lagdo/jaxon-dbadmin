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
    protected $ui;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * @var DbFacade
     */
    protected $db;

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
}
