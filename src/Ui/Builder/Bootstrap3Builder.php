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
    public function checkbox(bool $checked = false): BuilderInterface
    {
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('span', [
                'class' => 'input-group-addon',
                'style' => 'background-color:white;padding:8px;',
            ]);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $arguments = func_get_args();
        return parent::checkbox(...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function row(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('row ' . ltrim($class)),
        ];
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
        $this->scope->isInputGroup = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function text(string $class = ''): BuilderInterface
    {
        // A label in an input group must be wrapped into a span with class "input-group-addon".
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('span', ['class' => 'input-group-addon']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $attributes = ['class' => trim($class)];
        $this->tag('span', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addIcon(string $icon): BuilderInterface
    {
        return $this->addHtml('<span class="glyphicon glyphicon-' . $icon . '" aria-hidden="true" />');
    }

    /**
     * @inheritDoc
     */
    public function addCaret(): BuilderInterface
    {
        return $this->addHtml('<span class="caret" />');
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
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function button(string $style = 'default', string $class = '',
                           bool $fullWidth = false, bool $outline = false): BuilderInterface
    {
        // A button in an input group must be wrapped into a div with class "input-group-btn".
        // Check the parent scope.
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('div', ['class' => 'input-group-btn']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $btnClass = $fullWidth ? "btn btn-block btn-$style " : "btn btn-$style ";
        $attributes = [ 'class' => rtrim($btnClass . ltrim($class)), 'type' => 'button'];
        $this->tag('button', $attributes);
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
        $this->tag('select', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function option(string $title, bool $selected = false, string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('form-control ' . ltrim($class)),
        ];
        if ($selected) {
            $attributes['selected'] = 'selected';
        }
        $this->tag('option', $title, $attributes);
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
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelHeader(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-heading ' . ltrim($class)),
        ];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelBody(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-body ' . ltrim($class)),
        ];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function panelFooter(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('panel-footer ' . ltrim($class)),
        ];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function menu(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('list-group ' . ltrim($class)),
        ];
        $this->tag('div', $attributes);
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
        $this->tag('a', $title, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function breadcrumb(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('breadcrumb ' . ltrim($class)),
        ];
        $this->tag('ol', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function breadcrumbItem(string $label, string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('active ' . ltrim($class)),
        ];
        $this->tag('li', $label, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabHeader(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('nav nav-pills ' . ltrim($class)),
        ];
        $this->tag('ul', $attributes);
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
        $this->tag('li', $attributes);
        $attributes = ['data-toggle' => 'pill', 'href' => "#$id"];
        $this->tag('a', $label, $attributes);
        $this->end();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabContent(string $class = ''): BuilderInterface
    {
        $attributes = [
            'class' => rtrim('tab-content ' . ltrim($class)),
            'style' => 'margin-top:10px;',
        ];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabContentItem(string $id, bool $active, string $class = ''): BuilderInterface
    {
        $tabClass = $active ? 'tab-pane fade in active ' : 'tab-pane fade in ';
        $attributes = [ 'id' => $id, 'class' => rtrim($tabClass . ltrim($class))];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function table(bool $responsive, string $style = '', string $class = ''): BuilderInterface
    {
        if ($responsive) {
            $this->tag('div', ['class' => 'table-responsive']);
            $this->scope->isWrapper = true;
        }
        $tableClass = ($style) ? "table table-$style " : 'table ';
        $attributes = ['class' => rtrim($tableClass . ltrim($class))];
        $this->tag('table', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function form(bool $horizontal, bool $wrapped = true, string $class = ''): BuilderInterface
    {
        if ($wrapped) {
            $this->tag('div', ['class' => 'portlet-body form']);
            $this->scope->isWrapper = true;
        }
        $formClass = $horizontal ? 'form-horizontal ' : '';
        $attributes = ['class' => rtrim($formClass . ltrim($class))];
        $this->tag('form', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function formRow(string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim('form-group ' . ltrim($class))];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function dropdown(string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim('btn-group ' . ltrim($class)), 'role' => 'group'];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function dropdownItem(string $style = 'default', string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim("btn btn-sm btn-$style dropdown-toggle " . ltrim($class)),
            'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false'];
        $this->tag('button', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function dropdownMenu(string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim('dropdown-menu ' . ltrim($class))];
        $this->tag('ul', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function dropdownMenuItem(string $class = ''): BuilderInterface
    {
        $this->tag('li');
        $this->scope->isWrapper = true;
        $this->tag('a', ['class' => trim($class), 'href' => '#']);
        return $this;
    }
}
