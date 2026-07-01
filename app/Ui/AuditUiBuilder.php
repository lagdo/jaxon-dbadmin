<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Audit\Commands;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function array_filter;
use function in_array;
use function json_decode;
use function json_encode;
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
    public function content(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->col()
                        ->jxnBind(rq(Commands::class))
                        ->width(12)
                )
            )->setStyle('margin-right: 10px;')
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
        return $this->ui->tableRow(
            $this->ui->tableDataCell($lastUpdate)
                ->setStyle('width:120px;'),
            $this->ui->tableDataCell(
                $command['username'] . '<br/>' . $this->trans->lang($category)
            )->setStyle('width:180px;'),
            $this->ui->tableDataCell(
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
                $this->ui->tableBody(
                    $this->ui->each($commands, fn($command) =>
                        $this->command($command, $categories[$command['category']] ?? '')
                    )
                )
            )->border()
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
                $this->ui->div(
                    $this->ui->label($this->trans->lang('Category'))
                        ->setFor('category'),
                    $this->ui->select(
                        $this->ui->option('')
                            ->selected(false)->setValue(0),
                        $this->ui->each($categories, fn($category, $id) =>
                            $this->ui->option($this->trans->lang($category))
                                ->setValue($id)
                        )
                    )->setName('category')
                )->setStyle('margin-bottom: 10px;'),
                $this->ui->div(
                    $this->ui->label($this->trans->lang('User'))
                        ->setFor('username'),
                    $this->ui->input()
                        ->setType('text')
                        ->setName('username')
                )->setStyle('margin-bottom: 10px;'),
                $this->ui->div(
                    $this->ui->label($this->trans->lang('From'))
                        ->setFor('from_date'),
                    $this->ui->input()
                        ->setType('date')
                        ->setName('from_date'),
                    $this->ui->input()
                        ->setType('time')
                        ->setName('from_time')
                        ->setStyle('margin-top: 5px;')
                )->setStyle('margin-bottom: 10px;'),
                $this->ui->div(
                    $this->ui->label($this->trans->lang('To'))
                        ->setFor('to_date'),
                    $this->ui->input()
                        ->setType('date')
                        ->setName('to_date'),
                    $this->ui->input()
                        ->setType('time')
                        ->setName('to_time')
                        ->setStyle('margin-top: 5px;')
                )->setStyle('margin-bottom: 10px;'),
                $this->ui->div(
                    $this->ui->button($this->trans->lang('Show'))
                        ->primary()
                        ->jxnClick(rq(Commands::class)->show(form($formId)))
                )->setStyle('float:right;')
            )->setId($formId)
        );
    }
}
