<?php

namespace Lagdo\DbAdmin\Ui\Builder;

class Bootstrap4Builder extends AbstractBuilder
{
    /**
     * @inheritDoc
     */
    protected function getFormElementClass(string $tagName): string
    {
        if ($tagName === 'label') {
            return 'col-form-label';
        }
        return 'form-control';
    }

    /**
     * @inheritDoc
     */
    public function checkbox(bool $checked = false): BuilderInterface
    {
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('div', ['class' => 'input-group-append']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
            $this->tag('div', ['class' => 'input-group-text', 'style' => 'background-color:white;']);
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
        // Check the parent scope.
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('div', ['class' => 'input-group-prepend']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
            // Set the element class
            $class = rtrim('input-group-text ' . ltrim($class));
        }
        $attributes = ['class' => $class];
        $this->tag('label', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function buttonGroup(bool $fullWidth, string $class = ''): BuilderInterface
    {
        $btnClass = $fullWidth ? 'btn-group d-flex ' : 'btn-group ';
        $attributes = [
            'class' => rtrim($btnClass . ltrim($class)),
            'role' => 'group',
        ];
        $this->tag('div', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function button(string $title, string $style = 'secondary', string $class = '',
                           bool $fullWidth = false, bool $outline = false): BuilderInterface
    {
        if ($style === 'default') {
            $style = 'secondary'; // The default style is "secondary
        }
        // A button in an input group must be wrapped into a div with class "input-group-btn".
        // Check the parent scope.
        if ($this->scope !== null && $this->scope->isInputGroup) {
            $this->tag('div', ['class' => 'input-group-append']);
            // The new scope is a wrapper.
            $this->scope->isWrapper = true;
        }
        $btnClass = $fullWidth ? 'btn btn-block btn' : 'btn btn';
        if ($outline) {
            $btnClass .= '-outline';
        }
        $attributes = [
            'class' => rtrim("$btnClass-$style "  . ltrim($class)),
            'type' => 'button',
        ];
        $this->tag('button', $title, $attributes);
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
        $attributes = ($class) ? ['class' => trim($class)] : [];
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
        $this->options['card-style'] = $style;
        $attributes = [
            'class' => rtrim("card border-$style w-100 "  . ltrim($class)),
        ];
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
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
        $this->tag('div', $attributes);
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
        $this->tag('a', $title, $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function breadcrumb(string $class = ''): BuilderInterface
    {
        $this->tag('nav', ['aria-label' => 'breadcrumb']);
        $this->scope->isWrapper = true;
        $attributes = [
            'class' => rtrim('breadcrumb '  . ltrim($class)),
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
            'class' => rtrim('breadcrumb-item '  . ltrim($class)),
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
            'class' => rtrim('nav nav-pills '  . ltrim($class)),
        ];
        $this->tag('ul', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tabHeaderItem(string $id, bool $active, string $label, string $class = ''): BuilderInterface
    {
        $attributes = ['role' => 'presentation', 'class' => rtrim('nav-item ' . ltrim($class))];
        $this->tag('li', $attributes);
        $attributes = ['class' => $active ? 'nav-link active' : 'nav-link',
            'data-toggle' => 'tab', 'role' => 'tab', 'href' => "#$id"];
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
            'class' => rtrim('tab-content '  . ltrim($class)),
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
        $tabClass = $active ? 'tab-pane fade show active ' : 'tab-pane fade ';
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
        $attributes = ['class' => trim($class)];
        $this->tag('form', $attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function formRow(string $class = ''): BuilderInterface
    {
        $attributes = ['class' => rtrim('form-group row '  . ltrim($class))];
        $this->tag('div', $attributes);
        return $this;
    }
}
