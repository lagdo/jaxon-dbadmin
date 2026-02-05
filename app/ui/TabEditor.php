<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Jaxon\Script\Call\JxnCall;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function uniqid;

class TabEditor
{
    /**
     * @var DatabagPlugin
     */
    public static DatabagPlugin $databag;

    /**
     * @var string
     */
    public static string $page = '';

    /**
     * @param string $item
     *
     * @return string
     */
    public static function item(string $item = ''): string
    {
        return $item === '' ? self::id('', '') : self::id($item, '::');
    }

    /**
     * Set the tab item id on the components.
     *
     * @param HtmlComponent $component
     * @param JxnCall $xJsCall
     * @param string $item
     *
     * @return HtmlComponent
     */
    public static function bind(HtmlComponent $component,
        JxnCall $xJsCall, string $item = ''): HtmlComponent
    {
        $component->jxnBind($xJsCall, self::item($item));
        return $component;
    }

    /**
     * @return string
     */
    public static function zero(): string
    {
        // Different values depending on the page: server or database
        return 'ed-tab-zero-' . self::$page;
    }

    /**
     * @return string
     */
    public static function newId(): string
    {
        return 'ed-tab-' . uniqid();
    }

    /**
     * @return string
     */
    public static function names(): string
    {
        return 'editor.names.' . self::$page;
    }

    /**
     * @return string
     */
    public static function current(): string
    {
        return self::$databag->bag('dbadmin')->get('tab.editor', self::zero());
    }

    /**
     * Prefix the element id with the active tab id
     *
     * @param string $id
     * @param string $sep
     *
     * @return string
     */
    public static function id(string $id, string $sep = '_'): string
    {
        return TabApp::id(self::current() . "$sep$id");
    }

    /**
     * @return string
     */
    public static function zeroTitleId(): string
    {
        return TabApp::id(self::zero() . '_dbadmin-editor-tab-title');
    }

    /**
     * @return string
     */
    public static function titleId(): string
    {
        return self::id('dbadmin-editor-tab-title');
    }

    /**
     * @return string
     */
    public static function wrapperId(): string
    {
        return self::id('dbadmin-editor-tab-content');
    }
}
