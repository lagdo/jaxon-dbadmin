<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

use function html_entity_decode;

/**
 * This component displays the SQL query.
 */
#[Exclude]
class QueryText extends Component
{
    use QueryTrait;

    /**
     * The constructor
     *
     * @param Utils           $utils
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected Utils $utils, protected SelectUiBuilder $selectUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $query = $this->utils->html($this->stash()->get('select.query'));
        $query = html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this->selectUi->queryText($query);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server, ] = $this->getCurrentDb();
        $driver = $this->config()->getServerDriver($server);
        $this->response()->jo('jaxon.dbadmin')
            ->createSelectEditor($this->selectUi->queryTextId(), $driver);
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->stash()->set('select.query', $this->getSelectQuery());
        $this->render();
    }
}
