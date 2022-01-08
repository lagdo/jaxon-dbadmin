<?php

namespace Lagdo\DbAdmin\Ui\Html;

class HtmlScope
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $isInputGroup = false;

    /**
     * True if the scope was added to wrap another one, due to a framework requirement.
     *
     * @var bool
     */
    public $isWrapper = false;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var array
     */
    public $elements = [];

    /**
     * @var HtmlScope|null
     */
    public $parent;

    /**
     * @param string $name
     * @param HtmlScope|null $parent
     */
    public function __construct(string $name, $parent)
    {
        $this->name = $name;
        $this->parent = $parent;
    }
}
