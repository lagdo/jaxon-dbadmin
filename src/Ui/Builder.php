<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ui\Builder\BuilderInterface;

class Builder
{
    /**
     * @var BuilderInterface
     */
    protected $htmlBuilder;

    /**
     * @param BuilderInterface $htmlBuilder
     */
    public function __construct(BuilderInterface $htmlBuilder)
    {
        $this->htmlBuilder = $htmlBuilder;
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function home(array $values): string
    {
        $this->htmlBuilder->clear()
            ->row()->setId($values['containerId'])
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->select()->setId('adminer-dbhost-select');
        foreach($values['servers'] as $name => $title)
        {
            $this->htmlBuilder
                                    ->option($title)->setValue($name);
            if ($name == $values['default'])
            {
                $this->htmlBuilder
                                        ->setSelected('selected');
            }
            $this->htmlBuilder
                                    ->end();
        }
        $this->htmlBuilder
                                ->end()
                                ->button('Show', 'primary', 'btn-select')->setOnclick($values['connect'] . ';return false;')
                                ->end()
                            ->end()
                        ->end()
                        ->col(12)->setId($values['serverActionsId'])
                        ->end()
                        ->col(12)->setId($values['dbListId'])
                        ->end()
                        ->col(12)->setId($values['schemaListId'])
                        ->end()
                        ->col(12)->setId($values['dbActionsId'])
                        ->end()
                        ->col(12)->setId($values['dbMenuId'])
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->row()->setId($values['serverInfoId'])
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()->setId($values['breadcrumbsId'])
                            ->end()
                            ->span()->setId($values['mainActionsId'])
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)->setId($values['dbContentId'])
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function serverInfo(array $values): string
    {
        $this->htmlBuilder->clear()
            ->col(8)
                ->panel()
                    ->panelBody()->addHtml($values['server'])
                    ->end()
                ->end()
            ->end()
            ->col(4)
                ->panel()
                    ->panelBody()->addHtml($values['user'])
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $menuActions
     *
     * @return string
     */
    public function menuActions(array $menuActions): string
    {
        $this->htmlBuilder->clear()
            ->menu();
        foreach($menuActions as $id => $title)
        {
            $this->htmlBuilder
                ->menuItem($title, "adminer-menu-item menu-action-$id")->setId("adminer-menu-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $sqlActions
     *
     * @return string
     */
    public function menuCommands(array $sqlActions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(true);
        foreach($sqlActions as $id => $title)
        {
            $this->htmlBuilder
                ->button($title, 'default', 'adminer-menu-item', true)->setId("adminer-menu-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $databases
     *
     * @return string
     */
    public function menuDatabases(array $databases): string
    {
        $this->htmlBuilder->clear()
            ->inputGroup()
                ->select()->setId('adminer-dbname-select')
                    ->option('')->setValue('')
                    ->end();
        foreach($databases as $database)
        {
            $this->htmlBuilder
                    ->option($database)->setValue($database)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button('Show', 'primary', 'btn-select')->setId('adminer-dbname-select-btn')
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(array $schemas): string
    {
        $this->htmlBuilder->clear()
            ->inputGroup()
                ->select()->setId('adminer-schema-select');
        foreach ($schemas as $schema)
        {
            $this->htmlBuilder
                    ->option($schema)->setValue($schema)
                    ->end();
        }
        $this->htmlBuilder
                ->end()
                ->button('Show', 'primary', 'btn-select')->setId('adminer-schema-select-btn')
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $mainActions
     *
     * @return string
     */
    public function mainActions(array $mainActions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(false, 'adminer-main-action-group');
        foreach($mainActions as $id => $title)
        {
            $this->htmlBuilder
                ->button($title, 'default', '', true)->setId("adminer-main-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $breadcrumbs
     *
     * @return string
     */
    public function breadcrumbs(array $breadcrumbs): string
    {
        $this->htmlBuilder->clear()
            ->breadcrumb();
        foreach($breadcrumbs as $breadcrumb)
        {
            $this->htmlBuilder
                ->breadcrumbItem($breadcrumb)
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $this->htmlBuilder->clear()
            ->row()
                ->col(12)
                    ->tabHeader();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $this->htmlBuilder
                        ->tabHeaderItem("tab-content-$id", $active, $tab)
                        ->end();
            $active = false;
        }
        $this->htmlBuilder
                    ->end()
                    ->tabContent();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $this->htmlBuilder
                        ->tabContentItem("tab-content-$id", $active)
                        ->end();
            $active = false;
        }
        $this->htmlBuilder
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $content
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(string $content, string $counterId = ''): string
    {
        $this->htmlBuilder->clear()
            ->table(true, 'bordered')->addHtml($content)
            ->end();
        if (($counterId)) {
            $this->htmlBuilder
                ->panel()
                    ->panelBody()->addHtml('Selected (<span id="adminer-table-' . $counterId . '-count">0</span>)')
                    ->end()
                ->end();
        }
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $formId
     * @param array $user
     * @param string $privileges
     *
     * @return string
     */
    public function mainUser(string $formId,  array $user, string $privileges): string
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
}
