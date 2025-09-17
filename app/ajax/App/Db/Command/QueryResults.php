<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

use function intval;
use function trim;

/**
 * @exclude
 */
class QueryResults extends Component
{
    /**
     * The constructor
     *
     * @param DbFacade      $db         The facade to database functions
     * @param QueryUiBuilder $queryUi   The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected DbFacade $db,
        protected QueryUiBuilder $queryUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $results = $this->stash()->get('results');
        return $this->queryUi->results($results);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param array $values
     *
     * @return void
     */
    public function exec(array $values): void
    {
        $query = trim($values['query'] ?? '');
        if(!$query)
        {
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $limit = intval($values['limit'] ?? 0);
        $errorStops = $values['error_stops'] ?? false;
        $onlyErrors = $values['only_errors'] ?? false;
        $results = $this->db()->executeCommands($query, $limit, $errorStops, $onlyErrors);

        $this->stash()->set('results', $results['results']);
        $this->render();

        $this->stash()->set('select.duration', $results['duration']);
        $this->cl(Duration::class)->render();
        $this->cl(QueryHistory::class)->render();
    }
}
