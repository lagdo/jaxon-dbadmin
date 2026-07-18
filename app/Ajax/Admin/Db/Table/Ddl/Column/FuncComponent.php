<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\App\Ui\Table\ColumnUiBuilder;

use function trim;

abstract class FuncComponent extends BaseComponent
{
    use ColumnTrait;

    /**
     * @param ColumnUiBuilder   $columnUi   The HTML UI builder
     */
    public function __construct(protected ColumnUiBuilder $columnUi)
    {}

    /**
     * @param array $formValues
     *
     * @return array
     */
    protected function getTableFormValues(array $formValues): array
    {
        // Todo: check the validity of the form values.
        // Convert the boolean values
        $formValues['hasAutoIncrement'] = isset($formValues['hasAutoIncrement']);
        $formValues['setComment'] = isset($formValues['setComment']);

        return $formValues;
    }

    /**
     * @param array $formValues
     *
     * @return array
     */
    protected function getColumnFormValues(array $formValues): array
    {
        // Todo: check the validity of the form values.
        // Convert the boolean values
        $formValues['primary'] = isset($formValues['primary']);
        $formValues['autoIncrement'] = isset($formValues['autoIncrement']);
        $formValues['nullable'] = isset($formValues['nullable']);
        $formValues['setComment'] = isset($formValues['setComment']);

        $formValues['generated'] = trim($formValues['generated']);
        if ($formValues['generated'] === '') {
            $formValues['default'] = ''; // Erase the default value.
        }
        $formValues['comment'] ??= '';

        $formValues['fkDeferrable'] = isset($formValues['fkDeferrable']);
        if ($formValues['foreignKey'] === '') {
            $formValues['fkOnUpdate'] = '';
            $formValues['fkOnDelete'] = '';
            $formValues['fkDeferrable'] = false;
        }

        return $formValues;
    }
}
