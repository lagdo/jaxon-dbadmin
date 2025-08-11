<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Component;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;

/**
 * This class provides select query features on tables.
 */
class Values extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $options = $this->bag('dbadmin.select')->get('options', []);
        return $this->html->optionsValues($options);
    }

    /**
     * Change the query options
     *
     * @param int $limit
     *
     * @return void
     */
    public function saveSelectLimit(int $limit): void
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options');
        $options['limit'] = $limit;
        $this->bag('dbadmin.select')->set('options', $options);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }

    /**
     * Change the query options
     *
     * @param int $length
     *
     * @return void
     */
    public function saveTextLength(int $length): void
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options');
        $options['text_length'] = $length;
        $this->bag('dbadmin.select')->set('options', $options);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }
}
