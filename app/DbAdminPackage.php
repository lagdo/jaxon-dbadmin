<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Di\Container;
use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\AppFunc;
use Lagdo\DbAdmin\App\Ui\UiBuilder;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Config\ServerConfigProvider;
use Lagdo\DbAdmin\Support\Provider\PackageConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Secret\KeyBuilderInterface;
use Lagdo\DbAdmin\Support\Service\Export\FileSystemInterface;

use function count;
use function in_array;
use function realpath;
use function Jaxon\jaxon;
use function Jaxon\rq;
use function Lagdo\UiBuilder\Jaxon\registerUiBuilder;

/**
 * Jaxon DbAdmin package
 */
class DbAdminPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    /**
     * @var bool
     */
    private static bool $registered = false;

    /**
     * @param UiBuilder $ui
     */
    public function __construct(private UiBuilder $ui)
    {}

    /**
     * @inheritDoc
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/dbadmin.php');
    }

    /**
     * Helper function for the config middleware
     *
     * @param string $configDir
     * @param string $requestUri
     *
     * @return void
     */
    public static function register(string $configDir, string $requestUri): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        registerUiBuilder('template.name');

        $jaxon = jaxon();
        $jaxon->setOption('core.request.uri', $requestUri);
        $jaxon->setAppOption('assets.file', 'admin');

        $app = require "$configDir/app.php";
        $services = [];

        $auth = $app['auth'] ?? null;
        if ($auth !== null) {
            $services['dbadmin_auth_service'] = $auth;
        }
        $export = $app['export'] ?? null;
        if ($export !== null) {
            $services[FileSystemInterface::class] = $export;
        }
        $secret = $app['secret'] ?? [];
        if (isset($secret['reader']) && isset($secret['key'])) {
            $jaxon->setAppOption('container.alias.' .
                SecretConfigProvider::class, $secret['reader']);
            $services[KeyBuilderInterface::class] = $secret['key'];
        } else {
            $services[SecretConfigProvider::class] = fn() => new SecretConfigProvider();
        }

        if (count($services) > 0) {
            $jaxon->setAppOptions($services, 'container.set');
        }

        // Register the package.
        $foreigns = require "$configDir/foreigns.php";
        $jaxon->registerPackage(self::class, [
            ...($app['admin'] ?? []),
            'audit' => [
                'enabled' => $app['audit']['enabled'] ?? false,
                'users' => $app['audit']['users'] ?? [],
            ],
            'queries' => [
                'database' => $app['audit']['queries']['database'] ?? [],
                ...($app['admin']['queries'] ?? []),
            ],
            'provider' => static function(array $options, Container $di) use($configDir) {
                $configFile = "$configDir/servers.php";
                $provider = $di->g(PackageConfigProvider::class);
                return $provider->config($configFile)->getOptions($options);
            },
            'reader' => [
                'server' => ServerConfigProvider::class,
                'secret' => SecretConfigProvider::class,
            ],
            'foreigns' => $foreigns,
        ]);
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function checkAuditAccess(string $userId): bool
    {
        return !$this->getOption('audit.enabled', false) ? false :
            in_array($userId, $this->getOption('audit.users', []));
    }

    /**
     * @return string
     */
    private function editor(): string
    {
        return $this->getConfig()->getOption('ui.query.editor', 'cm');
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $editor = $this->editor();
        $html = $this->view()->render("dbadmin::editor::$editor/css");
        $assetsUrl = $this->getConfig()->getOption('ui.assets.url', '/dbadmin');
        $urls = [
            // Spinner CSS code.
            "$assetsUrl/app/spin.css",
            "$assetsUrl/app/layout.css",
            "$assetsUrl/app/styles.css",
            // DbAdmin tables CSS code.
            "$assetsUrl/app/table.css",
        ];

        return new CssCode(sHtml: $html, aUrls: $urls);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $editor = $this->editor();
        $html = $this->view()->render("dbadmin::editor::$editor/js");
        $assetsUrl = $this->getConfig()->getOption('ui.assets.url', '/dbadmin');
        $urls = [
            // Spinner javascript code.
            "$assetsUrl/app/spin.js",
            "$assetsUrl/app/script.js",
            "$assetsUrl/editor/index.js",
            "$assetsUrl/editor/$editor.js",
        ];

        return new JsCode(sHtml: $html, aUrls: $urls);
    }

    /**
     * Get the javascript code to include into the page
     *
     * The code must NOT be enclosed in HTML tags.
     *
     * @return string
     */
    public function getReadyScript(): string
    {
        return '{' . rq(AppFunc::class)->start() . '}';
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function layout(): string
    {
        return $this->ui->admin();
    }
}
