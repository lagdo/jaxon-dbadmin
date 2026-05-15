<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\App\Ui\Table\TableUiBuilder;

class Header extends Component
{
    /**
     * @param TableUiBuilder $tableUi
     */
    public function __construct(protected TableUiBuilder $tableUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $formValues = $this->getTableBag('formValues', []);
        return $this->tableUi->headerForm($formValues);
    }
}
