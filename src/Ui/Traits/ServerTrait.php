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
        $this->htmlBuilder->clear()
            ->col(8)
                ->panel()
                    ->panelBody()->addHtml($server)
                    ->end()
                ->end()
            ->end()
            ->col(4)
                ->panel()
                    ->panelBody()->addHtml($user)
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
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
        $this->htmlBuilder->clear()
            ->form(true)->setId($formId)
                ->formRow()
                    ->formCol(3)
                        ->formLabel()->setFor('host')
                            ->addText($user['host']['label'])
                        ->end()
                    ->end()
                    ->formCol(6)
                        ->formInput()->setType('text')->setName('host')
                            ->setValue($user['host']['value'])->setDataMaxlength('60')
                        ->end()
                    ->end()
                ->end()
                ->formRow()
                    ->formCol(3)
                        ->formLabel()->setFor('name')->addText($user['name']['label'])
                        ->end()
                    ->end()
                    ->formCol(6)
                        ->formInput()->setType('text')->setName('name')
                            ->setValue($user['name']['value'])->setDataMaxlength('80')
                        ->end()
                    ->end()
                ->end()
                ->formRow()
                    ->formCol(3)
                        ->formLabel()->setFor('pass')->addText($user['pass']['label'])
                        ->end()
                    ->end()
                    ->formCol(6)
                        ->formInput()->setType('text')->setName('pass')
                            ->setValue($user['pass']['value'])->setAutocomplete('new-password')
                        ->end()
                    ->end()
                    ->formCol(3, 'checkbox')
                        ->formLabel()->setFor('hashed')
                            ->checkbox($user['hashed']['value'])->setName('hashed')
                            ->end()
                            ->addText($user['hashed']['label'])
                        ->end()
                    ->end()
                ->end()
                ->addHtml($privileges)
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $formId
     * @param array $collations
     *
     * @return string
     */
    public function addDbForm(string $formId, array $collations): string
    {
        $this->htmlBuilder->clear()
            ->form(true)->setId($formId)
                ->formRow()
                    ->formCol(3)
                        ->formLabel()->setFor('name')->addText('Name')
                        ->end()
                    ->end()
                    ->formCol(6)
                        ->formInput()->setType('text')->setName('name')->setPlaceholder('Name')
                        ->end()
                    ->end()
                ->end()
                ->formRow()
                    ->formCol(3)
                        ->formLabel()->setFor('collation')->addText('Collation')
                        ->end()
                    ->end()
                    ->formCol(6)
                        ->select()
                            ->option(true, '(collation)')
                            ->end();
        foreach($collations as $group => $_collations)
        {
            $this->htmlBuilder
                            ->optgroup()->setLabel($group);
            foreach($_collations as $collation)
            {
                $this->htmlBuilder
                                ->option(false, $collation)
                                ->end();
            }
            $this->htmlBuilder
                            ->end();
        }
        $this->htmlBuilder
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }
}
