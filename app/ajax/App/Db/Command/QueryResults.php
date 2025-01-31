<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\Component;

use function intval;
use function trim;

/**
 * @exclude
 */
class QueryResults extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $results = $this->stash()->get('results');
        return $this->ui()->queryResults($results);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param array $values
     *
     * @return void
     */
    public function exec(array $values)
    {
        $query = trim($values['query'] ?? '');
        $limit = intval($values['limit'] ?? 0);
        $errorStops = $values['error_stops'] ?? false;
        $onlyErrors = $values['only_errors'] ?? false;

        if(!$query)
        {
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $results = $this->db()->executeCommands($query, $limit, $errorStops, $onlyErrors);
        $this->stash()->set('results', $results['results']);

        $this->render();
    }
}
