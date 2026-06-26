<?php

namespace Lagdo\DbAdmin\App\Ui\Tab;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Jaxon\Script\Call\JxnCall;
use Lagdo\UiBuilder\HtmlComponent;

use function uniqid;

/**
 * Manage ids for the editor tabs.
 */
class EditorTab
{
    /**
     * @var string
     */
    private string $page = '';

    /**
     * @param AppTab $appTab
     * @param DatabagPlugin $databag
     */
    public function __construct(private AppTab $appTab, private DatabagPlugin $databag)
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
     * @param string $page
     *
     * @return void
     */
    public function setPage(string $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function page(): string
    {
        return $this->page;
    }

    /**
     * @param string $page
     *
     * @return bool
     */
    public function onPage(string $page): bool
    {
        return $this->page === $page;
    }

    /**
     * @param string $item
     *
     * @return string
     */
    public function item(string $item = ''): string
    {
        return $item === '' ? $this->id('', '') : $this->id($item, '::');
    }

    /**
     * @return string
     */
    public function zero(): string
    {
        // Different values depending on the page: server or database
        return "ed-tab-zero-{$this->page}";
    }

    /**
     * @return string
     */
    public function newId(): string
    {
        return 'ed-tab-' . uniqid();
    }

    /**
     * @return string
     */
    public function names(): string
    {
        return "editor.names.{$this->page}";
    }

    /**
     * @return string
     */
    public function saved(): string
    {
        return "editor.saved.{$this->page}";
    }

    /**
     * @return string
     */
    public function current(): string
    {
        return $this->databag->bag('dbadmin')->get('tab.editor', $this->zero());
    }

    /**
     * Prefix the element id with the active tab id
     *
     * @param string $id
     * @param string $sep
     *
     * @return string
     */
    public function id(string $id, string $sep = '_'): string
    {
        return $this->appTab->id($this->current() . "$sep$id");
    }

    /**
     * @return string
     */
    public function zeroTitleId(): string
    {
        return $this->appTab->id($this->zero() . '_dbadmin-editor-tab-title');
    }

    /**
     * @return string
     */
    public function titleId(): string
    {
        return $this->id('dbadmin-editor-tab-title');
    }

    /**
     * @return string
     */
    public function wrapperId(): string
    {
        return $this->id('dbadmin-editor-tab-content');
    }
}
