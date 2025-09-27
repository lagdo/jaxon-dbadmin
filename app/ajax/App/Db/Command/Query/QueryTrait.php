<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

use function trim;

trait QueryTrait
{
    /**
     * @var QueryUiBuilder
     */
    protected QueryUiBuilder $queryUi;

    /**
     * @var string
     */
    private $database = '';

    /**
     * @var string
     */
    private $query = '';

    /**
     * @var string
     */
    private $queryId = 'dbadmin-main-command-query';

    /**
     * @return string
     */
    public function html(): string
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);

        $this->db()->prepareCommand();

        $defaultLimit = 20;
        return $this->queryUi->command($this->queryId, $this->rq(), $defaultLimit);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        [$server,] = $this->bag('dbadmin')->get('db');
        $driver = $this->package->getServerDriver($server);
        $this->response->jo('jaxon.dbadmin')->createSqlQueryEditor($this->queryId, $driver);
        if($this->query !== '')
        {
            $this->response->jo('jaxon.dbadmin')->setSqlQuery($this->query);
        }

        $this->cl(History::class)->render();
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param string $query
     * @param array $values
     *
     * @return void
     */
    public function exec(string $query, array $values): void
    {
        $this->db()->prepareCommand();

        $values['query'] = $query;
        $this->cl(Results::class)->exec($values);
    }

    /**
     * Save an SQL query in the user favorites
     *
     * @param string $query
     *
     * @return void
     */
    public function save(string $query): void
    {
        $query = trim($query);
        if(!$query)
        {
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $title = 'Add a favorite';
        $content = $this->queryUi->addFavoriteForm($query);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq(Favorites::class)->create($this->queryUi->addFavoriteValues()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }
}
