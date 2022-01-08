<?php

namespace Lagdo\DbAdmin\Ui\Builder;

use Lagdo\DbAdmin\Ui\Html\BuilderInterface;

class Bootstrap3Builder extends AbstractBuilder
{
    /**
     * @inheritDoc
     */
    public function row(string $class = ''): BuilderInterface
    {
        $class = rtrim('row ' . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function col(int $width = 12, string $class = ''): BuilderInterface
    {
        if ($width < 1 || $width > 12) {
            $width = 12; // Full width by default.
        }
        $class = rtrim("col-md-$width "  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inputGroup(string $class = ''): BuilderInterface
    {
        $class = rtrim('input-group ' . trim($class));
        $this->createScope('div')->setClass($class);
        $this->scope->isInputGroup = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function button(string $title, string $style = 'default', string $class = ''): BuilderInterface
    {
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->createScope('div')->setClass('input-group-btn');
            $this->scope->isInputGroup = true;
        }
        $class = rtrim("btn btn-$style "  . trim($class));
        $this->createScope('button', [$title])->setClass($class)->setType('button');
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function buttonGroup(string $class = ''): BuilderInterface
    {
        $class = rtrim('btn-group ' . trim($class));
        $this->createScope('div')->setClass($class)->setRole('group')->setAriaLabel('...');
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function select(string $class = ''): BuilderInterface
    {
        $class = rtrim('form-control ' . trim($class));
        $this->createScope('select')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function option(string $title, string $class = ''): BuilderInterface
    {
        $class = rtrim('form-control ' . trim($class));
        $this->createScope('option', [$title])->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panel(string $style = 'default', string $class = ''): BuilderInterface
    {
        $class = rtrim("panel panel-$style "  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelHeader(string $class = ''): BuilderInterface
    {
        $class = rtrim('panel-header '  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelBody(string $class = ''): BuilderInterface
    {
        $class = rtrim('panel-body '  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelFooter(string $class = ''): BuilderInterface
    {
        $class = rtrim('panel-footer '  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menu(string $class = ''): BuilderInterface
    {
        $class = rtrim('list-group '  . trim($class));
        $this->createScope('div')->setClass($class);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menuItem(string $title, string $class = ''): BuilderInterface
    {
        $class = rtrim('list-group-item ' . trim($class));
        $this->createScope('a', [$title])->setHref('javascript:void(0)')->setClass($class);
        return $this;
    }
}
