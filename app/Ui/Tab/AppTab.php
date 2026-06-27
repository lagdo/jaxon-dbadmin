<?php

namespace Lagdo\DbAdmin\App\Ui\Tab;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Jaxon\Script\Call\JxnCall;
use Lagdo\UiBuilder\HtmlComponent;

use function uniqid;

/**
 * Manage ids for the app tabs.
 */
class AppTab
{
    /**
     * @param DatabagPlugin $databag
     */
    public function __construct(private DatabagPlugin $databag)
    {}

    /**
     * Set the tab item id on the components.
     *
     * @param HtmlComponent $component
     * @param JxnCall $xJsCall
     * @param string $item
     *
     * @return HtmlComponent
     */
    public function bind(HtmlComponent $component,
        JxnCall $xJsCall, string $item = ''): HtmlComponent
    {
        $component->jxnBind($xJsCall, $this->item($item));
        return $component;
    }

    /**
     * @param string $item
     *
     * @return string
     */
    public function item(string $item = ''): string
    {
        return $item === '' ? $this->current() : $this->current() . "::$item";
    }

    /**
     * @return string
     */
    public function zero(): string
    {
        return 'app-tab-zero';
    }

    /**
     * @return string
     */
    public function newId(): string
    {
        return 'app-tab-' . uniqid();
    }

    /**
     * @return string
     */
    public function current(): string
    {
        return $this->databag->bag('dbadmin.app')->get('tab', $this->zero());
    }

    /**
     * Prefix the element id with the active tab id
     *
     * @param string $id
     *
     * @return string
     */
    public function id(string $id): string
    {
        return $this->current() . "_$id";
    }

    /**
     * @return string
     */
    public function zeroTitleId(): string
    {
        return $this->zero() . '_dbadmin-app-tab-title';
    }

    /**
     * @return string
     */
    public function titleId(): string
    {
        return $this->id('dbadmin-app-tab-title');
    }

    /**
     * @return string
     */
    public function wrapperId(): string
    {
        return $this->id('dbadmin-app-tab-content');
    }
}
