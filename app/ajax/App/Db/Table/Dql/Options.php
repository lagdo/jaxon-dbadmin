<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Component;

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
    private $formOptionsId = 'dbadmin-table-select-options-form';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $options = $this->stash()->get('select.options');
        // Click handlers on buttons
        $handlers = [
            'btnColumns' => $this->rq(Columns::class)->edit(),
            'btnFilters' => $this->rq(Filters::class)->edit(),
            'btnSorting' => $this->rq(Sorting::class)->edit(),
            'btnLimit' => $this->rq()
                ->saveSelectLimit(pm()->input("{$this->formOptionsId}-limit")->toInt()),
            'btnLength' => $this->rq()
                ->saveTextLength(pm()->input("{$this->formOptionsId}-length")->toInt()),
            'id' => [
                'limit' => "{$this->formOptionsId}-limit",
                'length' => "{$this->formOptionsId}-length",
            ],
        ];

        return $this->ui()->selectOptions($options, $handlers);
    }

    /**
     * Change the query options
     *
     * @param int $limit
     *
     * @return void
     */
    public function saveSelectLimit(int $limit)
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options');
        $options['limit'] = $limit;
        $this->bag('dbadmin.select')->set('options', $options);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }

    /**
     * Change the query options
     *
     * @param int $length
     *
     * @return void
     */
    public function saveTextLength(int $length)
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options');
        $options['text_length'] = $length;
        $this->bag('dbadmin.select')->set('options', $options);

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }
}
