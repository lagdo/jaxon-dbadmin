<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
use Lagdo\DbAdmin\Ajax\Base\Duration;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

#[Exclude]
class ImportResult extends Component
{
    /**
     * @var array
     */
    protected array $results;

    /**
     * The constructor
     *
     * @param QueryUiBuilder $queryUi   The HTML UI builder
     */
    public function __construct(protected QueryUiBuilder $queryUi)
    {}

    /**
     * @return Duration
     */
    protected function duration(): Duration
    {
        return $this->cl(ImportDuration::class)->item('upload');
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->queryUi->results($this->results);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(History::class)->render();
        $this->duration()->update($this->results['duration']);
    }

    /**
     * Set the query results
     *
     * @param array $results
     *
     * @return void
     */
    public function results(array $results): void
    {
        $this->results = $results;
        $this->render();
    }
}
