<?php

namespace Lagdo\DbAdmin\Ui\Builder;

use Lagdo\DbAdmin\Ui\Html\HtmlBuilder;
use LogicException;

abstract class AbstractBuilder extends HtmlBuilder implements BuilderInterface
{
    const BTN_PRIMARY = 1;
    const BTN_SECONDARY = 2;
    const BTN_DANGER = 4;
    const BTN_FULL_WIDTH = 8;
    const BTN_OUTLINE = 16;
    const BTN_SMALL = 32;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $tagName
     *
     * @return string
     */
    protected abstract function getFormElementClass(string $tagName): string;

    /**
     * @param string $method
     * @param array $arguments
     * @return $this
     * @throws LogicException When element is not initialized yet
     */
    public function __call(string $method, array $arguments)
    {
        $tagName = strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $method));
        if (stripos($tagName, 'set-') === 0) {
            if ($this->scope === null) {
                throw new LogicException('Attributes can be set for elements only');
            }
            $this->scope->attributes[substr($tagName, 4)] = $arguments[0] ?? null;
            return $this;
        }
        if (stripos($tagName, 'form-') === 0) {
            $tagName = substr($tagName, 5);
            $this->createScope($tagName, $arguments);
            $class = $this->scope->attributes['class'] ?? '';
            $this->scope->attributes['class'] = trim($this->getFormElementClass($tagName) . ' ' . $class);
            return $this;
        }
        return $this->createScope($tagName, $arguments);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    protected function createScope(string $name, array $arguments)
    {
        $this->scope = new Scope($name, $arguments, $this->scope);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function checkbox(bool $checked = false): BuilderInterface
    {
        $arguments = func_get_args();
        array_shift($arguments);
        $this->tag('input', ...$arguments);
        $this->scope->attributes['type'] = 'checkbox';
        if ($checked) {
            $this->scope->attributes['checked'] = 'checked';
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function radio(bool $checked = false): BuilderInterface
    {
        $arguments = func_get_args();
        array_shift($arguments);
        $this->tag('input', ...$arguments);
        $this->scope->attributes['type'] = 'radio';
        if ($checked) {
            $this->scope->attributes['checked'] = 'checked';
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function option(bool $selected = false): BuilderInterface
    {
        $arguments = func_get_args();
        array_shift($arguments);
        $this->tag('option', ...$arguments);
        if ($selected) {
            $this->scope->attributes['selected'] = 'selected';
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function formCol(int $width = 12, string $class = ''): BuilderInterface
    {
        return $this->col($width, $class);
    }

    /**
     * @inheritDoc
     */
    public function formRowTag(): string
    {
        return 'div';
    }

    /**
     * @return BuilderInterface
     */
    public function end(): BuilderInterface
    {
        parent::end();
        // Wrappers are scopes that were automatically added.
        // They also need to be automatically ended.
        while($this->scope !== null && $this->scope->isWrapper)
        {
            parent::end();
        }
        return $this;
    }
}
