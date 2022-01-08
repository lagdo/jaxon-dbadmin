<?php

namespace Lagdo\DbAdmin\Ui\Builder;

class Bootstrap4Builder extends AbstractBuilder
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
            'class' => rtrim('btn-group d-flex ' . ltrim($class)),
            'role' => 'group',
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
            $this->createScope('div', ['class' => 'input-group-append']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $btnClass = 'btn btn';
        if ($outline) {
            $btnClass .= '-outline';
        }
        $attributes = [
            'class' => rtrim("$btnClass-$style "  . ltrim($class)),
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
        $attributes = ($class) ? ['class' => trim($class)] : [];
        $this->createScope('option', $title, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panel(string $style = 'default', string $class = ''): BuilderInterface
    {
        $this->options['card-style'] = $style;
        $attributes = [
            'class' => rtrim("card border-$style w-100 "  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelHeader(string $class = ''): BuilderInterface
    {
        $style = $this->options['card-style'];
        $attributes = [
            'class' => rtrim("card-header border-$style "  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelBody(string $class = ''): BuilderInterface
    {
        $style = $this->options['card-style'];
        $attributes = [
            'class' => rtrim("card-body text-$style "  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelFooter(string $class = ''): BuilderInterface
    {
        $style = $this->options['card-style'];
        $attributes = [
            'class' => rtrim("card-footer border-$style "  . ltrim($class)),
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
            'class' => rtrim('list-group-item list-group-item-action ' . ltrim($class)),
            'href' => 'javascript:void(0)',
        ];
        $this->createScope('a', $title, $attributes);
        return $this;
    }
}
