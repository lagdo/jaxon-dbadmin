<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;

trait ServerTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param string $server
     * @param string $user
     *
     * @return string
     */
    public function serverInfo(string $server, string $user): string
    {
        $html = $this->builder();
        return $html->build(
            $html->col(
                $html->panel(
                    $html->panelBody($html->html($server))
                )
            )
            ->width(8),
            $html->col(
                $html->panel(
                    $html->panelBody($html->html($user))
                )
            )
            ->width(4),
        );
    }

    /**
     * @param string $formId
     * @param array $user
     * @param string $privileges
     *
     * @return string
     */
    public function userForm(string $formId, array $user, string $privileges): string
    {
        $html = $this->builder();
        return $html->build(
            $html->form(
                $html->formRow(
                    $html->formCol(
                        $html->formLabel($this->html->text($user['host']['label']))
                            ->setFor('host')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formInput()
                            ->setType('text')->setName('host')
                            ->setDataMaxlength('60')->setValue($user['host']['value'])
                    )
                    ->width(6),
                ),
                $html->formRow(
                    $html->formCol(
                        $html->formLabel($this->html->text($user['name']['label']))
                            ->setFor('name')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formInput()
                            ->setType('text')->setName('name')
                            ->setDataMaxlength('80')->setValue($user['name']['value'])
                    )
                    ->width(6),
                ),
                $html->formRow(
                    $html->formCol(
                        $html->formLabel($this->html->text($user['pass']['label']))
                            ->setFor('pass')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formInput()
                            ->setType('text')->setName('pass')
                            ->setAutocomplete('new-password')
                            ->setValue($user['pass']['value'])
                    )
                    ->width(6),
                    $html->formCol(
                        $html->checkbox()
                            ->setName('hashed')->checked($user['hashed']['value']),
                        $html->text($user['hashed']['label'])
                    )
                    ->width(3)
                    ->setClass('checkbox')
                ),
                $html->div($html->html($privileges))
            )
            ->responsive(true)->wrapped(true)->setId($formId)
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
        $html = $this->builder();
        return $html->build(
            $html->form(
                $html->formRow(
                    $html->formCol(
                        $html->formLabel($this->html->text('Name'))
                            ->setFor('name')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formInput()
                            ->setType('text')->setName('name')
                            ->setPlaceholder('Name')
                    )
                    ->width(6)
                ),
                $html->formRow(
                    $html->formCol(
                        $html->formLabel($this->html->text('Collation'))
                            ->setFor('collation')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formSelect(
                            $html->option('(collation)')->selected(true),
                            $html->each($collations, fn($_collations, $group) =>
                                $html->list(
                                    $html->optgroup()->setLabel($group),
                                    $html->each($_collations, fn($collation) =>
                                        $html->option($collation)
                                    )
                                )
                            )
                        )
                        ->setName('collation')
                    )
                    ->width(6)
                )
            )
            ->responsive(true)->wrapped(true)->setId($formId)
        );
    }
}
