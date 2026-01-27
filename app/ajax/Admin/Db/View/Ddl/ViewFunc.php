<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ViewUiBuilder;

class ViewFunc extends FuncComponent
{
    /**
     * The constructor
     *
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param ViewUiBuilder  $viewUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected ViewUiBuilder $viewUi, protected Translator $trans)
    {}

    /**
     * Create a new view
     *
     * @param array $values      The view values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function create(array $values): void
    {
        // $values['materialized'] = isset($values['materialized']);

        // $result = $this->db()->createView($values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->cl(Views::class)->show();
        // $this->alert()->success($result['message']);
    }

    /**
     * Update a given view
     *
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function update(string $view, array $values): void
    {
        // $values['materialized'] = isset($values['materialized']);

        // $result = $this->db()->updateView($view, $values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->show($view);
        // $this->alert()->success($result['message']);
    }

    /**
     * Drop a given view
     *
     * @param string $view        The view name
     *
     * @return void
     */
    public function drop(string $view): void
    {
        $result = $this->db()->dropView($view);
        if (isset($result['error'])) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($result['error']);
            return;
        }

        $this->cl(Views::class)->show();
        $this->showBreadcrumbs();

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($result['message']);
    }
}
