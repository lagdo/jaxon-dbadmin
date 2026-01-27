<?php

namespace Lagdo\DbAdmin\Ui\Database;

use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\DbAdmin\Ui\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function count;

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
     * @return string
     */
    public function userFormId(): string
    {
        return Tab::id('jaxon-dbadmin-user-form');
    }

    /**
     * @return string
     */
    public function dbFormId(): string
    {
        return Tab::id('jaxon-dbadmin-database-form');
    }

    /**
     * @param array $user
     * @param string $privileges
     *
     * @return string
     */
    public function addUserForm(array $user, string $privileges): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->ui->text($user['host']['label']))
                            ->setFor('host')
                    )->width(3),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')->setName('host')
                            ->setDataMaxlength('60')->setValue($user['host']['value'])
                    )->width(6),
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->ui->text($user['name']['label']))
                            ->setFor('name')
                    )->width(3),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')->setName('name')
                            ->setDataMaxlength('80')->setValue($user['name']['value'])
                    )->width(6),
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->ui->text($user['pass']['label']))
                            ->setFor('pass')
                    )->width(3),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')->setName('pass')
                            ->setAutocomplete('new-password')
                            ->setValue($user['pass']['value'])
                    )->width(6),
                    $this->ui->col(
                        $this->ui->checkbox()
                            ->setName('hashed')->checked($user['hashed']['value']),
                        $this->ui->text($user['hashed']['label'])
                    )->width(3)
                        ->setClass('checkbox')
                ),
                $this->ui->div($this->ui->html($privileges))
            )->wrapped(true)->setId($this->userFormId())
        );
    }

    /**
     * @param string $formId
     * @param array $collations
     *
     * @return string
     */
    public function addDbForm(array $collations): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->ui->text('Name'))
                                ->setFor('name')
                    )->width(3),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')->setName('name')
                            ->setPlaceholder('Name')
                    )->width(6)
                ),
                $this->ui->when(count($collations) === 0, fn() =>
                    $this->ui->input()
                        ->setType('hidden')
                        ->setName('collation')
                        ->setValue('')),
                $this->ui->when(count($collations) > 0, fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->label($this->ui->text('Collation'))
                                ->setFor('collation')
                        )->width(3),
                        $this->ui->col(
                            $this->ui->select(
                                $this->ui->option('(collation)')
                                    ->setValue('')
                                    ->selected(true),
                                $this->ui->each($collations, fn($_collations, $group) =>
                                    $this->ui->list(
                                        $this->ui->when($group !== '', fn() =>
                                            $this->ui->optgroup(
                                                $this->ui->each($_collations, fn($collation) =>
                                                    $this->ui->option($collation)
                                                )
                                            )->setLabel($group)
                                        ),
                                        $this->ui->when($group === '', fn() =>
                                            $this->ui->each($_collations, fn($collation) =>
                                                $this->ui->option($collation)
                                            )
                                        )
                                    )
                                )
                            )->setName('collation')
                        )->width(6)
                    )
                )
            )->wrapped(true)->setId($this->dbFormId())
        );
    }
}
