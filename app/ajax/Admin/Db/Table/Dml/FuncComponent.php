<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\ComponentTrait;
use Lagdo\DbAdmin\Ajax\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Data\EditUiBuilder;

#[Before('checkDatabaseAccess')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param EditUiBuilder  $editUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected EditUiBuilder $editUi, protected Translator $trans)
    {}

    /**
     * @param string $title
     * @param string $query
     * @param array $buttons
     *
     * @return void
     */
    protected function showSqlQueryForm(string $title, string $query, array $buttons = []): void
    {
        // Show the query in a modal dialog.
        $queryDivId = 'dbadmin-table-show-sql-query';
        $title = $this->trans()->lang($title);
        $content = $this->editUi->sqlCodeForm($queryDivId, $query);
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Close'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($query),
        ], ...$buttons];

        $this->modal()->show($title, $content, $buttons, $options);

        [$server, ] = $this->bag('dbadmin')->get('db');
        $driver = $this->package->getServerDriver($server);
        $this->response->jo('jaxon.dbadmin')->createSqlSelectEditor($queryDivId, $driver);
    }
}
