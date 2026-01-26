<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\App\Stash\Stash;
use Jaxon\Response\AjaxResponse;
use Jaxon\Script\Call\JxnCall;
use Lagdo\UiBuilder\Component\HtmlComponent;

class Tab
{
    /**
     * @var Stash
     */
    public static Stash $stash;

    /**
     * @return string
     */
    public static function current(): string
    {
        return self::$stash->get('tab.current', 'app-tab-zero');
    }

    /**
     * @param AjaxResponse $response
     *
     * @return void
     */
    public static function setCurrent(AjaxResponse $response): void
    {
        self::$stash->set('tab.current', $response->bag('dbadmin.tab')
            ->get('current', 'app-tab-zero'));
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
    public static function titleId(): string
    {
        return self::id('jaxon-dbadmin-tab-title');
    }

    /**
     * @return string
     */
    public static function wrapperId(): string
    {
        return self::id('jaxon-dbadmin-tab-content');
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
        $currentTab = self::$stash->get('tab.current', 'app-tab-zero');
        $component->jxnBind($xJsCall, $item === '' ? $currentTab : "$currentTab::$item");
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
        return self::bind($component, ...$arguments);
    }
}
