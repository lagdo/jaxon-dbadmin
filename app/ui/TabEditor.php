<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\Plugin\Response\Databag\DatabagPlugin;

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
    public static function current(): string
    {
        return self::$databag->bag('dbadmin')->get('tab.editor', self::zero());
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
        return TabApp::id(self::current() . "_$id");
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
