<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Audit\Commands;
use Lagdo\DbAdmin\App\Ajax\Audit\Page\AppUser;
use Lagdo\DbAdmin\App\Ajax\Audit\Page\DbServer;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Html\HtmlComponent;
use DateInterval;

use function array_filter;
use function intdiv;
use function in_array;
use function json_decode;
use function json_encode;
use function Jaxon\pm;
use function Jaxon\rq;

class AuditUiBuilder
{
    use AppTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param AuthInterface $auth
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, private AuthInterface $auth)
    {}

    /**
     * @return AuthInterface
     */
    protected function auth(): AuthInterface
    {
        return $this->auth;
    }

    /**
     * @return BuilderInterface
     */
    protected function ui(): BuilderInterface
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }

    /**
     * @return string
     */
    protected function audit(): string
    {
        return '';
    }

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
     * @param int $duration
     *
     * @return string
     */
    private function formatDuration(int $duration): string
    {
        $seconds = intdiv($duration, 1_000_000);
        $minutes = intdiv($seconds, 60);
        $seconds %= 60;
        $hours = intdiv($minutes, 60);
        $minutes %= 60;
        $interval = new DateInterval(sprintf('PT%dH%dM%dS', $hours, $minutes, $seconds));
        $interval->f = ($duration % 1_000_000) / 1_000_000;
        return $interval->format('%H:%I:%S.%F');
    }

    /**
     * @param array $command
     * @param string $category
     *
     * @return mixed
     */
    private function command(array $command, string $category): mixed
    {
        return $this->ui->tableRow(
            $this->ui->tableDataCell(
                $command['started_at'] . '<br/>' . $this->formatDuration($command['duration'])
            )->setStyle('width:220px;'),
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
                $this->ui->tableHead(
                    $this->ui->tableRow(
                        $this->ui->tableHeadCell('Date'),
                        $this->ui->tableHeadCell('User'),
                        $this->ui->tableHeadCell('Query')
                    )
                ),
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
     * @return HtmlComponent
     */
    private function sidebarForm(array $categories): HtmlComponent
    {
        $formId = 'dbadmin-sidebar-audit-form';
        return $this->ui->form(
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
                $this->ui->div(
                    $this->ui->label($this->trans->lang('From'))
                        ->setFor('from_date'),
                ),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->input()
                            ->setType('date')
                            ->setName('from_date'),
                    )->setStyle('width: 67%;'),
                    $this->ui->div(
                        $this->ui->input()
                            ->setType('time')
                            ->setName('from_time')
                    )->setStyle('width: 31%;')
                )->setStyle('display: flex; justify-content: space-between;')
            )->setStyle('margin-bottom: 10px;'),
            $this->ui->div(
                $this->ui->div(
                    $this->ui->label($this->trans->lang('To'))
                        ->setFor('to_date'),
                ),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->input()
                            ->setType('date')
                            ->setName('to_date'),
                    )->setStyle('width: 67%;'),
                    $this->ui->div(
                        $this->ui->input()
                            ->setType('time')
                            ->setName('to_time')
                    )->setStyle('width: 31%;')
                )->setStyle('display: flex; justify-content: space-between;')
            )->setStyle('margin-bottom: 10px;'),
            $this->ui->div(
                $this->ui->button($this->trans->lang('Show'))
                    ->primary()
                    ->jxnClick(rq(Commands::class)->show(pm()->form($formId)))
            )->setStyle('float:right;')
        )->setId($formId);
    }

    /**
     * @return HtmlComponent
     */
    private function builtWith(): HtmlComponent
    {
        return $this->ui()->card(
            $this->ui()->cardBody(
                $this->ui()->div(
                    'Built on <a style="text-decoration: none;" href="https://github.com/lagdo/dbadmin-mono" ' .
                        'target="_blank"><i class="fa-brands fa-github"></i></a> with the &middot; ' .
                        '<a style="text-decoration: none;" href="https://www.jaxon-php.org" ' .
                        'target="_blank">Jaxon Ajax library</a> &middot;'
                )
            )
        )->setStyle('margin-top: 10px;');
    }

    /**
     * @param array $categories
     *
     * @return string
     */
    public function sidebar(array $categories): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->sidebarForm($categories)
                )->setClass('jaxon-dbadmin-page-sidebar_block'),
                $this->ui->div('&nbsp;')
                    ->setClass('jaxon-dbadmin-page-sidebar_spacer'),
                $this->ui->div(
                    $this->ui->div()
                        ->jxnBind(rq(DbServer::class)),
                    $this->ui->div()
                        ->jxnBind(rq(AppUser::class)),
                    $this->ui->div($this->builtWith())
                )->setClass('jaxon-dbadmin-page-sidebar_block')
            )->setClass('jaxon-dbadmin-page-sidebar'),
        );
    }
}
