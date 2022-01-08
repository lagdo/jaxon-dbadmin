<?php

namespace Lagdo\DbAdmin\Ui\Builder;

interface BuilderInterface
{
    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function row(string $class = ''): BuilderInterface;

    /**
     * @param int $width
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function col(int $width = 12, string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function inputGroup(string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function buttonGroup(string $class = ''): BuilderInterface;

    /**
     * @param string $title
     * @param string $style
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function button(string $title, string $style = 'default', string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function select(string $class = ''): BuilderInterface;

    /**
     * @param string $title
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function option(string $title, string $class = ''): BuilderInterface;

    /**
     * @param string $style
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function panel(string $style = 'default', string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function panelHeader(string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function panelBody(string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function panelFooter(string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function menu(string $class = ''): BuilderInterface;

    /**
     * @param string $title
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function menuItem(string $title, string $class = ''): BuilderInterface;
}
