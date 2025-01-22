<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Options extends Component
{
    /**
     * The select form div id
     *
     * @var string
     */
    private $formOptionsId = 'adminer-table-select-options-form';

    /**
     * Default select options
     *
     * @var array
     */
    private $selectOptions = ['limit' => 50, 'text_length' => 100];

    /**
     * @inheritDoc
     */
    protected function before()
    {
        // Initialize select options
        $this->bag('dbadmin')->set('options', $this->selectOptions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $options = $this->stash()->get('select.options');
        // Click handlers on buttons
        $formOptions = pm()->form($this->formOptionsId);
        $handlers = [
            'btnColumns' => $this->rq(Columns::class)->edit(),
            'btnFilters' => $this->rq(Filters::class)->edit(),
            'btnSorting' => $this->rq(Sorting::class)->edit(),
            'btnLimit' => $this->rq()->save($formOptions),
            'btnLength' => $this->rq()->save($formOptions),
        ];

        return $this->ui->selectOptions($options, $handlers);
    }

    /**
     * Change the query options
     *
     * @param array  $formValues  The form values
     *
     * @return void
     */
    public function save(array $formValues)
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['limit'] = $formValues['limit'] ?? 50;
        $options['text_length'] = $formValues['text_length'] ?? 100;
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table.name');
        $selectData = $this->db->getSelectData($table, $options);

        // Display the new query
        $this->cl(Query::class)->show($selectData['query']);
    }
}
