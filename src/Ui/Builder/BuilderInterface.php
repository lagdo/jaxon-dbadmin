<?php

namespace Lagdo\DbAdmin\Ui\Builder;

interface BuilderInterface
{
    /**
     * @param bool $checked
     *
     * @return BuilderInterface
     */
    public function checkbox(bool $checked = false): BuilderInterface;

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
    public function text(string $class = ''): BuilderInterface;

    /**
     * @param bool $fullWidth
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function buttonGroup(bool $fullWidth, string $class = ''): BuilderInterface;

    /**
     * @param string $title
     * @param string $style
     * @param string $class
     * @param bool $fullWidth
     * @param bool $outline
     *
     * @return BuilderInterface
     */
    public function button(string $title, string $style = 'default', string $class = '',
                           bool $fullWidth = false, bool $outline = false): BuilderInterface;

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

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function breadcrumb(string $class = ''): BuilderInterface;

    /**
     * @param string $label
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function breadcrumbItem(string $label, string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function tabHeader(string $class = ''): BuilderInterface;

    /**
     * @param string $id
     * @param bool $active
     * @param string $label
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function tabHeaderItem(string $id, bool $active, string $label, string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function tabContent(string $class = ''): BuilderInterface;

    /**
     * @param string $id
     * @param bool $active
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function tabContentItem(string $id, bool $active, string $class = ''): BuilderInterface;

    /**
     * @param bool $responsive
     * @param string $style
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function table(bool $responsive, string $style = '', string $class = ''): BuilderInterface;

    /**
     * @param string $class
     * @param bool $horizontal
     *
     * @return BuilderInterface
     */
    public function form(bool $horizontal, string $class = ''): BuilderInterface;

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function formRow(string $class = ''): BuilderInterface;

    /**
     * @param int $width
     * @param string $class
     *
     * @return BuilderInterface
     */
    public function formCol(int $width = 12, string $class = ''): BuilderInterface;
}
