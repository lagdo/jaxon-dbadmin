<?php

namespace Lagdo\DbAdmin\Ui\Builder;

class Bootstrap3Builder extends AbstractBuilder
{
    /**
     * @inheritDoc
     */
    protected function getFormElementClass(string $tagName): string
    {
        if ($tagName === 'label') {
            return 'control-label';
        }
        return 'form-control';
    }

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
    public function buttonGroup(bool $fullWidth, string $class = ''): BuilderInterface
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

    /**
     * @inheritDoc
     */
    public function breadcrumb(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('breadcrumb '  . ltrim($class)),
        ];
        $this->createScope('ol', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function breadcrumbItem(string $label, string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('active '  . ltrim($class)),
        ];
        $this->createScope('li', $label, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabHeader(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('nav nav-pills '  . ltrim($class)),
        ];
        $this->createScope('ul', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabHeaderItem(string $id, bool $active, string $label, string $class = ''): BuilderInterface
    {
        $attributes = [
            'role' => 'presentation',
            'class' => rtrim(($active ? 'active ' : '')  . ltrim($class)),
        ];
        $this->createScope('li', $attributes);
        $attributes = ['data-toggle' => 'pill', 'href' => "#$id"];
        $this->createScope('a', $label, $attributes);
        $this->end();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabContent(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('tab-content '  . ltrim($class)),
        ];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabContentItem(string $id, bool $active, string $class = ''): BuilderInterface
    {
        $tabClass = $active ? 'tab-pane fade in active ' : 'tab-pane fade in ';
        $attributes = [ 'id' => $id, 'class' => rtrim($tabClass . ltrim($class))];
        $this->createScope('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function table(bool $responsive, string $style = '', string $class = ''): BuilderInterface
    {
        if ($responsive) {
            $this->createScope('div', ['class' => 'table-responsive']);
            $this->scope->isWrapper = true;
        }
        $tableClass = ($style) ? "table table-$style " : 'table ';
        $attributes = ['class' => rtrim($tableClass . ltrim($class))];
        $this->createScope('table', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function form(bool $horizontal, string $class = ''): BuilderInterface
    {
        $this->createScope('div', ['class' => 'portlet-body form']);
        $this->scope->isWrapper = true;
        $formClass = $horizontal ? 'form-horizontal ' : '';
        $attributes = ['class' => rtrim($formClass . ltrim($class))];
        $this->createScope('table', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function formRow(string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim('form-group '  . ltrim($class))];
        $this->createScope('div', $attributes);
        return $this;
    }
}
