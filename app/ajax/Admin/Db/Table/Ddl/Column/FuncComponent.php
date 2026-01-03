<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ColumnUiBuilder;

use function array_map;

#[Databag('dbadmin.table')]
abstract class FuncComponent extends BaseComponent
{
    /**
     * The database table data.
     *
     * @var array|null
     */
    private $metadata = null;

    /**
     * The columns data stored in the client.
     *
     * @var array|null
     */
    private $columns = null;

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableData($this->getTableName());
    }

    /**
     * @return array
     */
    protected function columns(): array
    {
        return $this->columns ??= $this->bag('dbadmin.table')->get('columns', []);
    }

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
     * @return ColumnEntity
     */
    protected function getEmptyColumn(): ColumnEntity
    {
        return new ColumnEntity($this->db()->getTableField());
    }

    /**
     * @param string $fieldName
     *
     * @return ColumnEntity|null
     */
    protected function getFieldColumn(string $fieldName): ColumnEntity|null
    {
        $column = $this->columns()[$fieldName] ?? null;
        if ($column === null) {
            return null;
        }

        $field = $column['status'] === 'added' ?
            // New column => empty field
            $this->db()->getTableField() :
            // Existing column => check the metadata
            ($this->metadata()['fields'][$fieldName] ?? null);

        // Fill the data from the database with the data from the databag.
        return $field === null ? null : ColumnEntity::make($field, $column);
    }

    /**
     * @return array<ColumnEntity>
     */
    protected function getTableColumns(): array
    {
        // Fill the data from the database with the data from the databag or the form values.
        return array_map(fn(array $column) =>
            $this->getFieldColumn($column['name']), $this->columns());
    }

    /**
     * @param array $formValues
     *
     * @return array
     */
    protected function getColumnValues(array $formValues): array
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
