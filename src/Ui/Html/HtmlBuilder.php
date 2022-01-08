<?php

/*
 * This file is part of the PhpHtmlBuilder package.
 *
 * (c) Andrew Polupanov <andrewfortalking@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lagdo\DbAdmin\Ui\Html;

use AvpLab\Element\Comment;
use AvpLab\Element\Element;
use AvpLab\Element\Text;
use AvpLab\Element\Tag;

/**
 * Provides API for easy building of HTML code in php
 * Copied from https://github.com/avplab/php-html-builder/blob/master/src/PhpHtmlBuilder.php,
 * and modified to change the properties' visibility from private to protected.
 */
class HtmlBuilder
{
    /**
     * @var Element[]
     */
    protected $elements = [];

    /**
     * @var \stdClass
     */
    protected $scope = null;

    /**
     * @param string $method
     * @param array $arguments
     * @return $this
     * @throws \LogicException When element is not initialized yet
     */
    public function __call(string $method, array $arguments)
    {
        $tagName = strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $method));
        if (stripos($tagName, 'set-') === 0) {
            if ($this->scope === null) {
                throw new \LogicException('Attributes can be set for elements only');
            }
            $this->scope->attributes[substr($tagName, 4)] = isset($arguments[0]) ? $arguments[0] : null;
        } else {
            $this->createScope($tagName, $arguments);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->elements = [];
        $this->scope = null;
        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    protected function createScope(string $name, array $arguments = [])
    {
        // Create new scope
        $scope = new HtmlScope($name, $this->scope);

        // resolve arguments
        if ($arguments) {
            if (is_array($arguments[0])) {
                $scope->attributes = $arguments[0];
            } else {
                $scope->elements[] = new Text($arguments[0], false);
                if (isset($arguments[1]) && is_array($arguments[1])) {
                    $scope->attributes = $arguments[1];
                }
            }
        }

        $this->scope = $scope;
        return $this;
    }

    private function addElementToScope(Element $element)
    {
        if ($this->scope) {
            $this->scope->elements[] = $element;
        } else {
            $this->elements[] = $element;
        }
    }

    /**
     * @param string $name
     * @return $this
     */
    public function tag(string $name)
    {
        $arguments = func_get_args();
        array_shift($arguments);
        $this->createScope($name, $arguments);
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function addText(string $text)
    {
        $element = new Text($text);
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function addHtml(string $html)
    {
        $element = new Text($html, false);
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function addComment(string $comment)
    {
        $element = new Comment($comment);
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @return $this
     * @throws \RuntimeException When element is not initialized yet.
     */
    public function end()
    {
        if ($this->scope === null) {
            throw new \RuntimeException('Abnormal element completion');
        }
        $element = new Tag($this->scope->name, $this->scope->attributes, $this->scope->elements);
        $this->scope = $this->scope->parent;
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @return $this
     * @throws \RuntimeException When element is not initialized yet.
     */
    public function endShorted()
    {
        if ($this->scope === null) {
            throw new \RuntimeException('Abnormal element completion');
        }
        $element = new Tag($this->scope->name, $this->scope->attributes);
        $element->setShort(true);
        $this->scope = $this->scope->parent;
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @return $this
     * @throws \RuntimeException When element is not initialized yet.
     */
    public function endOpened()
    {
        if ($this->scope === null) {
            throw new \RuntimeException('Abnormal element completion');
        }
        $element = new Tag($this->scope->name, $this->scope->attributes);
        $element->setOpened(true);
        $this->scope = $this->scope->parent;
        $this->addElementToScope($element);
        return $this;
    }

    /**
     * @return string
     */
    public function build()
    {
        $result = '';
        foreach ($this->elements as $element) {
            $result .= $element;
        }
        return $result;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }
}
