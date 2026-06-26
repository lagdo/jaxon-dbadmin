<?php

namespace Lagdo\DbAdmin\App\Ui\Tab;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;
use Lagdo\UiBuilder\HtmlComponent;
use LogicException;

class Tab
{
    /**
     * @var AppTab
     */
    private AppTab $appTab;

    /**
     * @var EditorTab
     */
    private EditorTab $editorTab;

    /**
     * @param DatabagPlugin $databag
     */
    public function __construct(DatabagPlugin $databag)
    {
        $this->appTab = new AppTab($databag);
        $this->editorTab = new EditorTab($this->appTab, $databag);
    }

    /**
     * @return AppTab
     */
    public function app(): AppTab
    {
        return $this->appTab;
    }

    /**
     * @return EditorTab
     */
    public function editor(): EditorTab
    {
        return $this->editorTab;
    }

    /**
     * @param HtmlComponent $component
     * @param string $tagName
     * @param string $method
     * @param array $arguments
     *
     * @return HtmlComponent
     */
    public function helper(HtmlComponent $component,
        string $tagName, string $method, array $arguments): HtmlComponent
    {
        if ($method === 'tbnBindApp') {
            return $this->appTab->bind($component, ...$arguments);
        }
        if ($method === 'tbnBindEditor') {
            return $this->editorTab->bind($component, ...$arguments);
        }

        throw new LogicException("Call to undefined method \"{$method}()\" in the TabApp helper.");
    }
}
