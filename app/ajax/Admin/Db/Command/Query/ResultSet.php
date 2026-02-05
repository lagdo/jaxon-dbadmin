<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;
use Lagdo\DbAdmin\Ui\TabEditor;

#[Exclude]
class ResultSet extends Component
{
    /**
     * @var array
     */
    private array $results;

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
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', TabEditor::item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->queryUi->results($this->results);
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
        $this->results = $results['results'];
        $this->render();

        $this->cl(History::class)->render();
        $this->cl(Duration::class)->update($results['duration']);
    }
}
