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
