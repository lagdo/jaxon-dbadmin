<?php

namespace Lagdo\DbAdmin\Ui\Html;

use AvpLab\Element\Text;

class HtmlScope
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var array
     */
    public $elements = [];

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
     * @var HtmlScope|null
     */
    public $parent = null;

    /**
     * The constructor
     *
     * @param string $name
     * @param HtmlScope|null $parent
     * @param array $arguments
     */
    public function __construct(string $name, ?HtmlScope $parent = null, array $arguments = [])
    {
        $this->name = $name;
        // Resolve arguments
        foreach ($arguments as $argument) {
            if (is_string($argument)) {
                $this->elements[] = new Text($argument, false);
            } elseif (is_array($argument)) {
                $this->attributes = $argument;
            }
        }
        $this->parent = $parent;
    }
}
