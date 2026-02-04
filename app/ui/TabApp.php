<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Jaxon\Script\Call\JxnCall;
use Lagdo\UiBuilder\Component\HtmlComponent;
use LogicException;

use function uniqid;

class TabApp
{
    /**
     * @var DatabagPlugin
     */
    public static DatabagPlugin $databag;

    /**
     * @param string $item
     *
     * @return string
     */
    public static function item(string $item = ''): string
    {
        return $item === '' ? self::current() : self::current() . "::$item";
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
    private static function bind(HtmlComponent $component,
        JxnCall $xJsCall, string $item = ''): HtmlComponent
    {
        $component->jxnBind($xJsCall, self::item($item));
        return $component;
    }

    /**
     * @param HtmlComponent $component
     * @param string $tagName
     * @param string $method
     * @param array $arguments
     *
     * @return HtmlComponent
     */
    public static function helper(HtmlComponent $component,
        string $tagName, string $method, array $arguments): HtmlComponent
    {
        if ($method === 'tbnBindApp') {
            return self::bind($component, ...$arguments);
        }
        if ($method === 'tbnBindEditor') {
            return TabEditor::bind($component, ...$arguments);
        }

        throw new LogicException("Call to undefined method \"{$method}()\" in the TabApp helper.");
    }

    /**
     * @return string
     */
    public static function zero(): string
    {
        return 'app-tab-zero';
    }

    /**
     * @return string
     */
    public static function newId(): string
    {
        return 'app-tab-' . uniqid();
    }

    /**
     * @return string
     */
    public static function current(): string
    {
        return self::$databag->bag('dbadmin')->get('tab.app', self::zero());
    }

    /**
     * Prefix the element id with the active tab id
     *
     * @param string $id
     *
     * @return string
     */
    public static function id(string $id): string
    {
        return self::current() . "_$id";
    }

    /**
     * @return string
     */
    public static function zeroTitleId(): string
    {
        return self::zero() . '_dbadmin-app-tab-title';
    }

    /**
     * @return string
     */
    public static function titleId(): string
    {
        return self::id('dbadmin-app-tab-title');
    }

    /**
     * @return string
     */
    public static function wrapperId(): string
    {
        return self::id('dbadmin-app-tab-content');
    }
}
