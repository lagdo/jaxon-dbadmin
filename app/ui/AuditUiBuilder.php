<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\Audit\Commands;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function array_filter;
use function in_array;
use function json_decode;
use function json_encode;
use function Jaxon\cl;
use function Jaxon\form;
use function Jaxon\rq;

class AuditUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col($this->ui->h3($this->trans->lang('Commands'))
                    ->setStyle('font-size:18px; margin:5px 0;')
                )->width(3),
                $this->ui->col(
                    $this->ui->nav()
                        ->jxnPagination(cl(Commands::class))
                        ->setStyle('float:right;')
                )->width(9)
            ),
            $this->ui->row(
                $this->ui->col()
                    ->tbnBind(rq(Commands::class))
                    ->width(12)
            )
        );
    }

    /**
     * @param array $command
     *
     * @return string
     */
    private function commandOptions(array $command): string
    {
        $optionNames = ['driver', 'name', 'host', 'username', 'database', 'schema'];
        $options = json_decode($command['options'], true);
        return json_encode(array_filter($options, fn($name) =>
            in_array($name, $optionNames), ARRAY_FILTER_USE_KEY));
    }

    /**
     * @param array $command
     * @param string $category
     *
     * @return mixed
     */
    private function command(array $command, string $category): mixed
    {
        $lastUpdate = str_replace(' ', '<br/>', $command['last_update']);
        return $this->ui->tr(
            $this->ui->td($lastUpdate)
                ->setStyle('width:120px;'),
            $this->ui->td(
                $command['username'] . '<br/>' . $this->trans->lang($category)
            )->setStyle('width:180px;'),
            $this->ui->td(
                $this->ui->div($this->commandOptions($command))
                    ->setStyle('font-weight:500;'),
                $this->ui->div($command['query'])
                    ->setStyle('font-weight:300;'),
            )->setStyle('font-size:12px;')
        );
    }

    /**
     * @param array $commands
     * @param array $categories
     *
     * @return string
     */
    public function commands(array $commands, array $categories): string
    {
        if (!$commands) {
            return $this->trans->lang('No commands.');
        }

        return $this->ui->build(
            $this->ui->table(
                $this->ui->tbody(
                    $this->ui->each($commands, fn($command) =>
                        $this->command($command, $categories[$command['category']] ?? '')
                    )
                ),
            )->responsive(true)->look('bordered')
        );
    }

    /**
     * @param array $categories
     *
     * @return string
     */
    public function sidebar(array $categories): string
    {
        $formId = 'dbadmin-sidebar-audit-form';
        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('Category'))
                            ->setFor('category')
                    )->width(12),
                    $this->ui->col(
                        $this->ui->select(
                            $this->ui->option('')
                                ->selected(false)->setValue(0),
                            $this->ui->each($categories, fn($category, $id) =>
                                $this->ui->option($this->trans->lang($category))
                                    ->setValue($id)
                            )
                        )->setName('category')
                    )->width(12)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('User'))
                            ->setFor('username')
                    )->width(12),
                    $this->ui->col(
                        $this->ui->input()->setType('text')
                            ->setName('username')
                    )->width(12)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('From'))
                            ->setFor('from_date')
                    )->width(12),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('date')->setName('from_date')
                    )->width(7)
                        ->setStyle('padding-right:1px'),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('time')->setName('from_time')
                    )->width(5)
                        ->setStyle('padding-left:1px')
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('To'))
                            ->setFor('to_date')
                    )->width(12),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('date')->setName('to_date')
                    )->width(7)
                        ->setStyle('padding-right:1px'),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('time')->setName('to_time')
                    )->width(5)
                        ->setStyle('padding-left:1px')
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->button($this->trans->lang('Show'))
                            ->primary()
                            ->jxnClick(rq(Commands::class)
                                ->show(form($formId)))
                    )->width(12)
                )->setStyle('padding-top: 10px; float:right;')
            )->setId($formId)->horizontal(false)->wrapped(true)
                ->setStyle('margin-top:16px;margin-right:12px;')
        );
    }
}
