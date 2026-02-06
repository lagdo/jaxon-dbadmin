<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
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
     * @param QueryUiBuilder $queryUi   The HTML UI builder
     */
    public function __construct(protected QueryUiBuilder $queryUi)
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
        return $this->queryUi->results($this->results['results']);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(History::class)->render();
        $this->cl(Duration::class)->update($this->results['duration']);
    }

    /**
     * Set the query results
     *
     * @param array $results
     *
     * @return self
     */
    public function results(array $results): self
    {
        $this->results = $results;
        return $this;
    }
}
