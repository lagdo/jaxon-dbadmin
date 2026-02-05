<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Table\ColumnUiBuilder;

use function trim;

abstract class FuncComponent extends BaseComponent
{
    use ColumnTrait;

    /**
     * The constructor
     *
     * @param ColumnUiBuilder   $columnUi   The HTML UI builder
     */
    public function __construct(protected ColumnUiBuilder $columnUi)
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
        $formValues['generated'] = trim($formValues['generated']);
        if ($formValues['generated'] === '') {
            $formValues['default'] = ''; // Erase the default value.
        }

        return $formValues;
    }
}
