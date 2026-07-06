<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\FuncComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;

/**
 * This class toggles the foreign fields loading.
 */
class Foreigns extends FuncComponent
{
    /**
     * @return void
     */
    public function toggle(): void
    {
        // Save the new value in the databag.
        $foreigns = (bool)$this->getParamValue('foreigns');
        $this->saveParamValue('foreigns', !$foreigns);

        // Execute the new query
        $this->cl(ResultSet::class)->page($this->getParamValue('page'));
    }
}
