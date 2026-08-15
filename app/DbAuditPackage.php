<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Audit\Commands;
use Lagdo\DbAdmin\App\Ui\UiBuilder;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Config\ServerConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Secret\KeyBuilderInterface;

use function realpath;
use function Jaxon\jaxon;
use function Jaxon\rq;
use function Lagdo\UiBuilder\Jaxon\registerUiBuilder;

/**
 * Jaxon DbAdmin audit package
 */
class DbAuditPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
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
     * Get the path to the config file
     *
     * @return string|array
     */
    public static function config(): string
    {
        return realpath(__DIR__ . '/../config/dbaudit.php');
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
        $jaxon->setAppOption('assets.file', 'audit');

        $app = require "$configDir/app.php";
        $services = [];

        $auth = $app['auth'] ?? null;
        if ($auth !== null) {
            $services['dbadmin_auth_service'] = $auth;
        }
        $secret = $app['secret'] ?? null;
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
        $jaxon->registerPackage(self::class, [
            'audit' => $app['audit'] ?? [],
            'reader' => [
                'server' => ServerConfigProvider::class,
                'secret' => SecretConfigProvider::class,
            ],
        ]);
    }

    /**
     * Get a given server options
     *
     * @return array
     */
    public function getServerOptions(): array
    {
        return $this->getOption('database', []);
    }

    /**
     * Get the driver of a given server
     *
     * @return string
     */
    public function getServerDriver(): string
    {
        return $this->getOption('database.driver', '');
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $assetsUrl = $this->getConfig()->getOption('ui.assets.url', '/dbadmin');
        $urls = [
            // Spinner CSS code.
            "$assetsUrl/app/spin.css",
            "$assetsUrl/app/layout.css",
            "$assetsUrl/app/styles.css",
            // DbAdmin tables CSS code.
            "$assetsUrl/app/table.css",
        ];

        return new CssCode(aUrls: $urls);
    }

    /**
     * @inheritDoc
     */
    public function getJsCode(): JsCode
    {
        $assetsUrl = $this->getConfig()->getOption('ui.assets.url', '/dbadmin');
        $urls = [
            // Spinner javascript code.
            "$assetsUrl/app/spin.js",
            "$assetsUrl/app/script.js",
        ];

        return new JsCode(aUrls: $urls);
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
        return '{' . rq(Commands::class)->page() . '}';
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function layout(): string
    {
        return $this->ui->audit();
    }
}
