<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

#[Exclude]
class Results extends Component
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
     * Display the query results
     *
     * @param array $results
     *
     * @return void
     */
    public function renderResults(array $results): void
    {
        $this->stash()->set('results', $results['results']);
        $this->render();

        $this->stash()->set('select.duration', $results['duration']);
        $this->cl(Duration::class)->render();
        $this->cl(History::class)->render();
    }
}
