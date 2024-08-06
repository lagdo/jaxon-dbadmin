<?php

namespace Lagdo\DbAdmin\App\Ajax\Page;

use Jaxon\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Db\Table;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Select;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Query;
use Lagdo\DbAdmin\App\Ajax\Db\User;
use Lagdo\DbAdmin\App\Ajax\Db\View;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\PageBuilder;

use function Jaxon\jq;
use function Jaxon\rq;
use function Jaxon\pm;

class PageActions extends Component
{
    /**
     * @var array
     */
    private $actions;

    /**
     * @param PageBuilder $ui
     * @param Translator $trans
     */
    public function __construct(private PageBuilder $ui, private Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->pageActions($this->actions);
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function databases()
    {
        $this->actions = [
            'add-database' => [
                'title' => $this->trans->lang('Create database'),
                'handler' => rq(Database::class)->add()],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function userPrivileges()
    {
        $this->actions = [
            'user' => [
                'title' => $this->trans->lang('Create user'),
                'handler' => rq(User::class)->add(),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbTables()
    {
        $this->actions = [
            'add-table' => [
                'title' => $this->trans->lang('Create table'),
                'handler' => rq(Table::class)->add(),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbViews()
    {
        $this->actions = [
            'add-view' => [
                'title' => $this->trans->lang('Create view'),
                'handler' => rq(View::class)->add(),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbRoutines()
    {
        $this->actions = [
            // 'add-procedure' => [
            //     'title' => $this->trans->lang('Create procedure'),
            // ],
            // 'add-function' => [
            //     'title' => $this->trans->lang('Create function'),
            // ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbSequences()
    {
        $this->actions = [
            // 'add-sequence' => [
            //     'title' => $this->trans->lang('Create sequence'),
            // ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbUserTypes()
    {
        $this->actions = [
            // 'add-type' => [
            //     'title' => $this->trans->lang('Create type'),
            // ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function dbEvents()
    {
        $this->actions = [
            // 'add-event' => [
            //     'title' => $this->trans->lang('Create event'),
            // ],
        ];
        $this->render();
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param bool $new New item options, false for no new item
     *
     * @return array
     */
    // protected function getTableLinks(bool $new = true): array
    // {
    //     $links = [
    //         'select' => $this->trans->lang('Select data'),
    //     ];
    //     if ($this->driver->support('table') || $this->driver->support('indexes')) {
    //         $links['table'] = $this->trans->lang('Show structure');
    //     }
    //     if ($this->driver->support('table')) {
    //         $links['alter'] = $this->trans->lang('Alter table');
    //     }
    //     if ($new) {
    //         $links['edit'] = $this->trans->lang('New item');
    //     }
    //     // $links['docs'] = \doc_link([$this->driver->jush() => $this->driver->tableHelp($name)], '?');

    //     return $links;
    // }

    /**
     * @exclude
     *
     * @param string $table
     *
     * @return void
     */
    public function showTable(string $table)
    {
        // $this->actions = $this->getTableLinks();

        // $this->actions = [
        //     'create' => $this->trans->lang('Alter indexes'),
        // ];

        // // From table.inc.php
        // $this->actions = [
        //     $this->trans->lang('Add foreign key'),
        // ];

        // $this->actions = [
        //     $this->trans->lang('Add trigger'),
        // ];

        $this->actions = [
            'edit-table' => [
                'title' => $this->trans->lang('Alter table'),
                'handler' => rq(Table::class)->edit($table),
            ],
            'drop-table' => [
                'title' => $this->trans->lang('Drop table'),
                'handler' => rq(Table::class)->drop($table)->confirm("Drop table $table?"),
            ],
            'select-table' => [
                'title' => $this->trans->lang('Select'),
                'handler' => rq(Select::class)->render(),
            ],
            'insert-table' => [
                'title' => $this->trans->lang('New item'),
                'handler' => rq(Query::class)->showInsert(),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $formId
     *
     * @return void
     */
    public function addTable(string $formId)
    {
        // Set main menu buttons
        $contentId = 'adminer-database-content';
        $length = jq(".{$formId}-column", "#$contentId")->length;
        $values = pm()->form($formId);
        $this->actions = [
            'table-save' => [
                'title' => $this->trans->lang('Save'),
                'handler' => rq(Table::class)->create($values)->when($length),
            ],
            'table-cancel' => [
                'title' => $this->trans->lang('Cancel'),
                'handler' => rq(Database::class)->showTables(),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $table
     * @param string $formId
     *
     * @return void
     */
    public function editTable(string $table, string $formId)
    {
        $values = pm()->form($formId);
        $this->actions = [
            'table-save' => [
                'title' => $this->trans->lang('Save'),
                'handler' => rq(Table::class)->alter($table, $values)
                    ->confirm("Save changes on table $table?"),
            ],
            'table-cancel' => [
                'title' => $this->trans->lang('Cancel'),
                'handler' => rq(Table::class)->show($table),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $table
     *
     * @return void
     */
    public function showSelect(string $table)
    {
        $this->actions = [
            'select-exec' => [
                'title' => $this->trans->lang('Execute'),
                'handler' => rq(Select::class)->execSelect(),
            ],
            'insert-table' => [
                'title' => $this->trans->lang('New item'),
                'handler' => rq(Query::class)->showInsert(),
            ],
            'select-back' => [
                'title' => $this->trans->lang('Back'),
                'handler' => rq(Table::class)->show($table),
            ],
        ];
        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $table
     * @param string $queryFormId
     * @param bool $isInsert
     *
     * @return void
     */
    public function showQuery(string $table, string $queryFormId, bool $isInsert)
    {
        $options = pm()->form($queryFormId);
        $this->actions = [
            'query-back' => [
                'title' => $this->trans->lang('Back'),
                'handler' => rq(Table::class)->show($table),
            ],
            'query-save' => [
                'title' => $this->trans->lang('Save'),
                'handler' => rq()->execInsert($options, true)
                    ->confirm($this->trans->lang('Save this item?')),
            ],
        ];
        if ($isInsert) {
            $this->actions['query-save-select'] = [
                'title' => $this->trans->lang('Save and select'),
                'handler' => rq()->execInsert($options, false)
                    ->confirm($this->trans->lang('Save this item?')),
            ];
        }
        $this->render();
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param bool $new New item options, NULL for no new item
     *
     * @return array
     */
    // protected function getViewLinks(bool $new = false): array
    // {
    //     $links = [
    //         'select' => $this->trans->lang('Select data'),
    //     ];
    //     if ($this->driver->support('indexes')) {
    //         $links['table'] = $this->trans->lang('Show structure');
    //     }
    //     if ($this->driver->support('table')) {
    //         $links['table'] = $this->trans->lang('Show structure');
    //         $links['alter'] = $this->trans->lang('Alter view');
    //     }
    //     if ($new) {
    //         $links['edit'] = $this->trans->lang('New item');
    //     }
    //     // $links['docs'] = \doc_link([$this->driver->jush() => $this->driver->tableHelp($name)], '?');

    //     return $links;
    // }

    /**
     * @exclude
     *
     * @param string $view
     *
     * @return void
     */
    public function showView(string $view)
    {
        // $this->actions = [
        //     $this->trans->lang('Add trigger'),
        // ];

        // $this->actions = $this->getViewLinks();

        $this->actions = [
            'edit-view' => [
                'title' => $this->trans->lang('Edit view'),
                'handler' => rq(View::class)->edit($view),
            ],
            'drop-view' => [
                'title' => $this->trans->lang('Drop view'),
                'handler' => rq(View::class)->drop($view)->confirm("Drop view $view?"),
            ],
        ];
        $this->render();
    }
}
