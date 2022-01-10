<?php

namespace Lagdo\DbAdmin\Ui\Builder;

use Lagdo\DbAdmin\Ui\Html\HtmlBuilder;

abstract class AbstractBuilder extends HtmlBuilder implements BuilderInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @inheritDoc
     */
    public function checkbox(bool $checked = false): BuilderInterface
    {
        $arguments = func_get_args();
        array_shift($arguments);
        $this->createScope('input', $arguments);
        $this->scope->attributes['type'] = 'checkbox';
        if ($checked) {
            $this->scope->attributes['checked'] = 'checked';
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
