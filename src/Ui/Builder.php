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
     * @return mixed
     */
    public function serverInfo(array $values)
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
     * @return mixed
     */
    public function menuActions(array $menuActions)
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
     * @return mixed
     */
    public function menuCommands(array $sqlActions)
    {
        $this->htmlBuilder->clear()
            ->buttonGroup();
        foreach($sqlActions as $id => $title)
        {
            $this->htmlBuilder
                ->button($title, 'default', 'adminer-menu-item')->setId("adminer-menu-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $databases
     *
     * @return mixed
     */
    public function menuDatabases(array $databases)
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
     * @return mixed
     */
    public function menuSchemas(array $schemas)
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
}
