<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Plugin\AbstractPackage;
use Jaxon\Plugin\CssCode;
use Jaxon\Plugin\CssCodeGeneratorInterface;
use Jaxon\Plugin\JsCode;
use Jaxon\Plugin\JsCodeGeneratorInterface;
use Lagdo\DbAdmin\App\Ajax\Audit\AppFunc;
use Lagdo\DbAdmin\App\Ui\AuditUiBuilder;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Config\ServerConfigProvider;

use function in_array;
use function realpath;
use function Jaxon\jaxon;
use function Jaxon\rq;

/**
 * Jaxon DbAdmin audit package
 */
class DbAuditPackage extends AbstractPackage implements CssCodeGeneratorInterface, JsCodeGeneratorInterface
{
    use PackageConfigTrait;

    /**
     * @param AuditUiBuilder $ui
     */
    public function __construct(private AuditUiBuilder $ui)
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

        $jaxon = jaxon();
        $jaxon->setOption('core.request.uri', $requestUri);
        $jaxon->setAppOption('assets.file', 'audit');

        $app = require "$configDir/app.php";
        self::registerAppServices($app);

        // Register the package.
        $jaxon->registerPackage(self::class, [
            ...($app['audit'] ?? []),
            'ui' => $app['ui'],
            'reader' => [
                'server' => ServerConfigProvider::class,
                'secret' => SecretConfigProvider::class,
            ],
        ]);
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function checkAccess(string $userId): bool
    {
        return !$this->getOption('enabled', false) ? false :
            in_array($userId, $this->getOption('users', []));
    }

    /**
     * @inheritDoc
     */
    public function getCssCode(): CssCode
    {
        $assetsUrl = $this->getConfig()->getOption('ui.assets.url', '/dbadmin');
        // PureCSS framework.
        $html = '
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
        return '{' . rq(AppFunc::class)->start() . '}';
    }

    /**
     * Get the HTML code of the package home page
     *
     * @return string
     */
    public function layout(): string
    {
        return $this->ui->layout();
    }
}
