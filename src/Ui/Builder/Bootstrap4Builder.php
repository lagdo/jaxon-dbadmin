<?php

namespace Lagdo\DbAdmin\Ui\Builder;

use Lagdo\DbAdmin\Ui\Html\BuilderInterface;

class Bootstrap4Builder extends AbstractBuilder
{
    /**
     * @inheritDoc
     */
    public function row(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function col(int $width = 12, string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inputGroup(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function button(string $title, string $style = 'default', string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function buttonGroup(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function select(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function option(string $title, string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panel(string $style = 'default', string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelHeader(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelBody(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelFooter(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menu(string $class = ''): BuilderInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menuItem(string $title, string $class = ''): BuilderInterface
    {
        return $this;
    }
}