<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Di\Container;
use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\AppFunc;
use Lagdo\DbAdmin\App\Ui\AdminUiBuilder;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Config\ServerConfigProvider;
use Lagdo\DbAdmin\Support\Provider\PackageConfigProvider;

use function file_exists;
use function realpath;
use function Jaxon\jaxon;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin package
 */
class DbAdminPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    use PackageConfigTrait;

    /**
     * @param AdminUiBuilder $ui
     */
    public function __construct(private AdminUiBuilder $ui)
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

        $jaxon = jaxon();
        $jaxon->setOption('core.request.uri', $requestUri);
        $jaxon->setAppOption('assets.file', 'admin');

        // Register the services.
        $app = require "$configDir/app.php";
        $app = self::registerServices($app, $configDir, $jaxon);

        // Register the package.
        $databaseFile = match(true) {
            file_exists("$configDir/database.json") => "$configDir/database.json",
            file_exists("$configDir/database.yaml") => "$configDir/database.yaml",
            file_exists("$configDir/database.yml") => "$configDir/database.yml",
            default => "$configDir/database.php",
        };
        $foreigns = require "$configDir/foreigns.php";
        $jaxon->registerPackage(self::class, [
            'ui' => $app['ui'],
            'audit' => [
                'enabled' => $app['audit']['enabled'] ?? false,
                'users' => $app['audit']['users'] ?? [],
                'database' => $app['audit']['database'] ?? [],
            ],
            'reader' => [
                'server' => ServerConfigProvider::class,
                'secret' => SecretConfigProvider::class,
            ],
            'foreigns' => $foreigns,
            'provider' => static fn(array $options, Container $di) =>
                $di->g(PackageConfigProvider::class)
                    ->config($databaseFile)
                    ->getOptions($options),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $config = $this->getConfig();
        $editor = $config->getOption('ui.query.editor', 'cm');
        $assetsUrl = $config->getOption('ui.assets.url', '/dbadmin');
        $html = $this->view()->render("dbadmin::editor::$editor/css");
        // PureCSS framework.
        $html .= '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/pure-min.css" integrity="sha384-X38yfunGUhNzHpBaEBsWLO+A0HDYOQi8ufWDkZ0k9e0eXz/tH3II7uKZ9msv++Ls" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/grids-responsive-min.css">';
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
        $config = $this->getConfig();
        $editor = $config->getOption('ui.query.editor', 'cm');
        $assetsUrl = $config->getOption('ui.assets.url', '/dbadmin');
        $html = $this->view()->render("dbadmin::editor::$editor/js");
        $urls = [
            // Spinner javascript code.
            "$assetsUrl/app/spin.js",
            "$assetsUrl/app/script.js",
            "$assetsUrl/editor/index.js",
            "$assetsUrl/editor/$editor.js",
        ];

        return new JsCode(sHtml: "$html", aUrls: $urls);
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
}
