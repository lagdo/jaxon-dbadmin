<?php

namespace Lagdo\DbAdmin\Ui\Database;

use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\UiBuilder\BuilderInterface;

class ServerUiBuilder
{
    use PageTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param string $formId
     * @param array $user
     * @param string $privileges
     *
     * @return string
     */
    public function addUserForm(string $formId, array $user, string $privileges): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->formLabel($this->ui->text($user['host']['label']))
                            ->setFor('host')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formInput()
                            ->setType('text')->setName('host')
                            ->setDataMaxlength('60')->setValue($user['host']['value'])
                    )
                    ->width(6),
                ),
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->formLabel($this->ui->text($user['name']['label']))
                            ->setFor('name')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formInput()
                            ->setType('text')->setName('name')
                            ->setDataMaxlength('80')->setValue($user['name']['value'])
                    )
                    ->width(6),
                ),
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->formLabel($this->ui->text($user['pass']['label']))
                            ->setFor('pass')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formInput()
                            ->setType('text')->setName('pass')
                            ->setAutocomplete('new-password')
                            ->setValue($user['pass']['value'])
                    )
                    ->width(6),
                    $this->ui->formCol(
                        $this->ui->checkbox()
                            ->setName('hashed')->checked($user['hashed']['value']),
                        $this->ui->text($user['hashed']['label'])
                    )
                    ->width(3)
                    ->setClass('checkbox')
                ),
                $this->ui->div($this->ui->html($privileges))
            )->wrapped(true)->setId($formId)
        );
    }

    /**
     * @param string $formId
     * @param array $collations
     *
     * @return string
     */
    public function addDbForm(string $formId, array $collations): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->formLabel($this->ui->text('Name'))
                            ->setFor('name')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formInput()
                            ->setType('text')->setName('name')
                            ->setPlaceholder('Name')
                    )
                    ->width(6)
                ),
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->formLabel($this->ui->text('Collation'))
                            ->setFor('collation')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formSelect(
                            $this->ui->option('(collation)')->selected(true),
                            $this->ui->each($collations, fn($_collations, $group) =>
                                $this->ui->list(
                                    $this->ui->optgroup()->setLabel($group),
                                    $this->ui->each($_collations, fn($collation) =>
                                        $this->ui->option($collation)
                                    )
                                )
                            )
                        )
                        ->setName('collation')
                    )
                    ->width(6)
                )
            )->wrapped(true)->setId($formId)
        );
    }
}
