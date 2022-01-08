<?php

namespace Lagdo\DbAdmin\Ui\Builder;

use Lagdo\DbAdmin\Ui\Html\HtmlBuilder;
use Lagdo\DbAdmin\Ui\Html\BuilderInterface;

abstract class AbstractBuilder extends HtmlBuilder implements BuilderInterface
{
    /**
     * @return BuilderInterface
     */
    public function end(): BuilderInterface
    {
        if($this->scope !== null && $this->scope->parent !== null && $this->scope->parent->isInputGroup)
        {
            if($this->scope->name === 'button')
            {
                // A button in an input group is automatically embedded in a div.
                // So there is an additional scope to end.
                parent::end();
            }
        }
        parent::end();
        return $this;
    }
}
