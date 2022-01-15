<?php

namespace Lagdo\DbAdmin\Ui\Html;

use AvpLab\Element\Text;

use function is_string;
use function is_array;

class Scope
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var Scope|null
     */
    public $parent;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var array
     */
    public $elements = [];

    /**
     * The constructor
     *
     * @param string $name
     * @param array $arguments
     * @param Scope|null $parent
     */
    public function __construct(string $name, array $arguments = [], ?Scope $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        // Resolve arguments
        foreach ($arguments as $argument) {
            if (is_string($argument)) {
                $this->elements[] = new Text($argument, false);
            } elseif (is_array($argument)) {
                $this->attributes = $argument;
            }
        }
    }
}
