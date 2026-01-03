<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ColumnUiBuilder;

#[Databag('dbadmin.table')]
abstract class FuncComponent extends BaseComponent
{
    use ColumnTrait;

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
     * @param array $formValues
     *
     * @return array
     */
    protected function getUserFormValues(array $formValues): array
    {
        // Todo: check the validity of the form values.
        // Convert the boolean values
        $formValues['primary'] = isset($formValues['primary']);
        $formValues['autoIncrement'] = isset($formValues['autoIncrement']);
        $formValues['nullable'] = isset($formValues['nullable']);
        $formValues['hasDefault'] = isset($formValues['hasDefault']);
        if (!$formValues['hasDefault']) {
            $formValues['default'] = ''; // Erase the default value.
        }

        return $formValues;
    }
}
