<?php

namespace Lagdo\DbAdmin\Ui\Builder;

class Bootstrap3Builder extends AbstractBuilder
{
    /**
     * @inheritDoc
     */
    public function row(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('row ' . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
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
        $attributes = [
            'class' => rtrim("col-md-$width "  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inputGroup(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('input-group ' . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        $this->scope->isInputGroup = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function buttonGroup(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('btn-group ' . ltrim($class)),
            'role' => 'group',
            'aria-label' => '...',
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function button(string $title, string $style = 'default', string $class = '', bool $outline = false): BuilderInterface
    {
        // A button in an input group must be wrapped into a div with class "input-group-btn".
        // Check the parent scope.
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->createScope('div', ['class' => 'input-group-btn']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $attributes = [
            'class' => rtrim("btn btn-$style "  . ltrim($class)),
            'type' => 'button',
        ];
        $this->createScope('button', $title, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function select(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('form-control ' . ltrim($class)),
        ];
        $this->createScope('select', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function option(string $title, string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('form-control ' . ltrim($class)),
        ];
        $this->createScope('option', $title, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panel(string $style = 'default', string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim("panel panel-$style "  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelHeader(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-heading '  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelBody(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-body '  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelFooter(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-footer '  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menu(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('list-group '  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menuItem(string $title, string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('list-group-item ' . ltrim($class)),
            'href' => 'javascript:void(0)',
        ];
        $this->createScope('a', $title, $attributes);
        return $this;
    }
}
