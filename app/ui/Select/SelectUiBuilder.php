<?php

namespace Lagdo\DbAdmin\Ui\Select;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\cl;
use function Jaxon\rq;
use function sprintf;

class SelectUiBuilder
{
    /**
     * @var string
     */
    private const QUERY_TEXT_CLASS = 'dbadmin-table-select-query';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return string
     */
    public function queryTextId(): string
    {
        return Tab::id(self::QUERY_TEXT_CLASS);
    }

    /**
     * @param string $queryText
     *
     * @return string
     */
    public function queryText(string $queryText): string
    {
        return $this->ui->build(
            $this->ui->div($queryText)
                ->setClass(self::QUERY_TEXT_CLASS)
                ->setId($this->queryTextId())
        );
    }

    /**
     * @param float $duration
     *
     * @return string
     */
    public function duration(float $duration): string
    {
        return $this->ui->build(
            $this->ui->inputGroup(
                $this->ui->label(sprintf('%.4f&nbsp;s', $duration))
            )
        );
    }

    /**
     * @return string
     */
    public function formId(): string
    {
        return Tab::id('dbadmin-table-select-options-form');
    }

    /**
     * @return string
     */
    public function home(): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->div(
                            $this->ui->row(
                                $this->ui->col()
                                    ->width(6)
                                    ->tbnBind(rq(Options\Fields::class)),
                                $this->ui->col()
                                    ->width(6)
                                    ->tbnBind(rq(Options\Values::class))
                            )
                        ),
                        $this->ui->row(
                            $this->ui->col(
                                $this->ui->panel(
                                    $this->ui->panelBody()
                                        ->setStyle('padding: 0 1px;')
                                        ->tbnBind(rq(QueryText::class))
                                )->look('default')
                                    ->setStyle('padding: 5px;')
                            )->width(12)
                        ),
                    )->wrapped(true)
                        ->setId($this->formId())
                )->width(12)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->buttonGroup(
                        $this->ui->button($this->ui->text($this->trans->lang('Edit')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick(rq(Select::class)->edit()),
                        $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                            ->fullWidth()->primary()
                            ->jxnClick(rq(ResultSet::class)->page())
                    )->fullWidth(true)
                )->width(3),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->nav()
                                ->jxnPagination(cl(ResultSet::class))
                                ->setId(Tab::id('jaxon-dbadmin-resulset-pagination'))
                        )->width(10)
                            ->setStyle('overflow:hidden'),
                        $this->ui->col()
                            ->width(2)
                            ->tbnBind(rq(Duration::class))
                    )
                )->width(9),
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(ResultSet::class))
            )
        );
    }
}
