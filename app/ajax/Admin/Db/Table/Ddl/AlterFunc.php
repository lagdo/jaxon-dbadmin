<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ColumnUiBuilder;

/**
 * Alter a table
 */
#[Databag('dbadmin.table')]
class AlterFunc extends FuncComponent
{
    use Column\ColumnTrait;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-alter-form';

    /**
     * The constructor
     *
     * @param DbAdminPackage    $package    The DbAdmin package
     * @param DbFacade          $db         The facade to database functions
     * @param ColumnUiBuilder   $columnUi   The HTML UI builder
     * @param Translator        $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected ColumnUiBuilder $columnUi, protected Translator $trans)
    {}

    /**
     * @param array  $values      The table values
     *
     * @return void
     */
    public function changes(array $values): void
    {
        $title = 'Changes in table ' . $this->getTableName();
        $content = $this->columnUi->formId($this->formId)
            ->changes($this->getTableColumns());
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];

        $this->modal()->show($title, $content, $buttons);
    }
}
