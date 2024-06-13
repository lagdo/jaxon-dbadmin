<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\JsCall\AttrFormatter;
use Lagdo\DbAdmin\App\Ajax\Db\Database;
use Lagdo\UiBuilder\AbstractBuilder;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\pm;
use function Jaxon\rq;

class MenuBuilder
{
    /**
     * @param BuilderInterface $htmlBuilder
     * @param AttrFormatter $attr
     */
    public function __construct(private BuilderInterface $htmlBuilder, private AttrFormatter $attr)
    {}

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
     * @param array $actions
     *
     * @return string
     */
    public function menuActions(array $actions): string
    {
        $this->htmlBuilder->clear()
            ->menu();
        foreach($actions as $action)
        {
            $this->htmlBuilder
                ->menuItem($action[0])
                    ->setClass("adminer-menu-item")
                    ->setJxnClick($this->attr->func($action[1]))
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $actions
     *
     * @return string
     */
    public function menuCommands(array $actions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(true);
        foreach($actions as $action)
        {
            $this->htmlBuilder
                ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                    ->setClass('adminer-menu-item')
                    ->addText($action[0])
                    ->setJxnClick($this->attr->func($action[1]))
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
                ->formSelect()->setId('jaxon-dbadmin-database-select')
                    ->option(false, '')
                    ->end();
        foreach($databases as $database)
        {
            $this->htmlBuilder
                    ->option(false, $database)
                    ->end();
        }
        $database = pm()->select('jaxon-dbadmin-database-select');
        $call = rq(Database::class)->select($database)->when($database);
        $this->htmlBuilder
                ->end()
                ->button(AbstractBuilder::BTN_PRIMARY)
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->setJxnClick($this->attr->func($call))
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $database
     * @param array $schemas
     *
     * @return string
     */
    public function menuSchemas(string $database, array $schemas): string
    {
        $this->htmlBuilder->clear()
            ->inputGroup()
                ->formSelect()->setId('jaxon-dbadmin-schema-select');
        foreach ($schemas as $schema)
        {
            $this->htmlBuilder
                    ->option(false, $schema)
                    ->end();
        }
        $schema =  pm()->select('jaxon-dbadmin-schema-select');
        $call = rq(Database::class)->select($database, $schema);
        $this->htmlBuilder
                ->end()
                ->button(AbstractBuilder::BTN_PRIMARY)
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->setJxnClick($this->attr->func($call))
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }
}
