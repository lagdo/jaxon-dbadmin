<?php

namespace Lagdo\DbAdmin\Ui\Traits;

trait ServerTrait
{
    /**
     * @param string $server
     * @param string $user
     *
     * @return string
     */
    public function serverInfo(string $server, string $user): string
    {
        return $this->html->build(
            $this->html->col(
                $this->html->panel(
                    $this->html->panelBody()->addHtml($server)
                )
            )
            ->width(8),
            $this->html->col(
                $this->html->panel(
                    $this->html->panelBody()->addHtml($user)
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
        return $this->html->build(
            $this->html->form(
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->formLabel()
                            ->setFor('host')->addText($user['host']['label'])
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formInput()
                            ->setType('text')->setName('host')
                            ->setDataMaxlength('60')->setValue($user['host']['value'])
                    )
                    ->width(6),
                ),
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->formLabel()
                            ->setFor('name')->addText($user['name']['label'])
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formInput()
                            ->setType('text')->setName('name')
                            ->setDataMaxlength('80')->setValue($user['name']['value'])
                    )
                    ->width(6),
                ),
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->formLabel()
                            ->setFor('pass')->addText($user['pass']['label'])
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formInput()
                            ->setType('text')->setName('pass')
                            ->setAutocomplete('new-password')
                            ->setValue($user['pass']['value'])
                    )
                    ->width(6),
                    $this->html->formCol(
                        $this->html->checkbox()
                            ->setName('hashed')->checked($user['hashed']['value']),
                        $this->html->text($user['hashed']['label'])
                    )
                    ->width(3)
                    ->setClass('checkbox')
                ),
                $this->html->div()->addHtml($privileges)
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
        return $this->html->build(
            $this->html->form(
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->formLabel()
                            ->setFor('name')->addText('Name')
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formInput()
                            ->setType('text')->setName('name')
                            ->setPlaceholder('Name')
                    )
                    ->width(6)
                ),
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->formLabel()
                            ->setFor('collation')->addText('Collation')
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formSelect(
                            $this->html->option('(collation)')->selected(true),
                            $this->html->each($collations, fn($_collations, $group) =>
                                $this->html->list(
                                    $this->html->optgroup()->setLabel($group),
                                    $this->html->each($_collations, fn($collation) =>
                                        $this->html->option($collation)
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
