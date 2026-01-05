<?php

namespace Lagdo\DbAdmin\Ui\Select;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\rq;
use function sprintf;

class SelectUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param string $queryDivId
     * @param string $queryText
     *
     * @return string
     */
    public function queryText(string $queryDivId, string $queryText): string
    {
        return $this->ui->build(
            $this->ui->div($queryText)->setId($queryDivId)
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
     * @param string $formId
     *
     * @return string
     */
    public function home(string $formId): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->div(
                            $this->ui->row(
                                $this->ui->col()
                                    ->width(6)
                                    ->jxnBind(rq(Options\Fields::class)),
                                $this->ui->col()
                                    ->width(6)
                                    ->jxnBind(rq(Options\Values::class))
                            )
                        ),
                        $this->ui->row(
                            $this->ui->col(
                                $this->ui->panel(
                                    $this->ui->panelBody()
                                        ->setStyle('padding: 0 1px;')
                                        ->jxnBind(rq(QueryText::class))
                                )->look('default')
                                    ->setStyle('padding: 5px;')
                            )->width(12)
                        ),
                    )->wrapped(true)->setId($formId)
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
                                ->jxnPagination(rq(ResultSet::class))
                        )->width(10)
                            ->setStyle('overflow:hidden'),
                        $this->ui->col()
                            ->width(2)
                            ->jxnBind(rq(Duration::class))
                    )
                )->width(9),
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(ResultSet::class))
            )
        );
    }
}
