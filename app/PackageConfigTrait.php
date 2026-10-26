<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\Ajax\Jaxon;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Support\DiAlias\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Secret\KeyBuilderInterface;
use Lagdo\DbAdmin\Support\Service\Export\FileSystemInterface;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Jaxon\Builder;
use Closure;

use function class_exists;
use function file_exists;
use function is_a;
use function is_array;
use function is_string;

trait PackageConfigTrait
{
    /**
     * @var bool
     */
    private static bool $registered = false;

    /**
     * @param Closure|string|null $definition
     *
     * @return Closure|null
     */
    private static function serviceDefinition(Closure|string|null $definition): Closure|null
    {
        return match(true) {
            is_a($definition, Closure::class) => $definition,
            class_exists($definition) => fn(Container $di) => $di->make($definition),
            default => null,
        };
    }

    /**
     * @param Closure|string|null $auth
     *
     * @return array
     */
    private static function registerAuth(Closure|string|null $auth): array
    {
        return $auth === null ? [] :
            [AuthInterface::class => self::serviceDefinition($auth)];
    }

    /**
     * @param Closure|string|null $export
     *
     * @return array
     */
    private static function registerExport(Closure|string|null $export): array
    {
        return $export === null ? [] :
            [FileSystemInterface::class => self::serviceDefinition($export)];
    }

    /**
     * @param array $secret
     * @param Jaxon $jaxon
     *
     * @return array
     */
    private static function registerSecret(array $secret, Jaxon $jaxon): array
    {
        if (!isset($secret['reader']) || !isset($secret['key'])) {
            return [SecretConfigProvider::class => fn() => new SecretConfigProvider()];
        }

        $jaxon->setAppOption('container.alias.' .
            SecretConfigProvider::class, $secret['reader']);
        return [KeyBuilderInterface::class => self::serviceDefinition($secret['key'])];
    }

    /**
     * @param array $template
     * @param Jaxon $jaxon
     *
     * @return array
     */
    private static function registerUiBuilder(array $template, Jaxon $jaxon): array
    {
        $jaxon->setAppOption('template', $template['name']);
        return [BuilderInterface::class => static fn(Container $di) =>
            Builder::init($di->make($template['builder']))];
    }

    /**
     * @param array $app
     * @param string $servicesDir
     * @param Jaxon $jaxon
     *
     * @return array
     */
    private static function appConfig(array $app, string $servicesDir, Jaxon $jaxon): array
    {
        $defined = [
            'auth' => is_string($app['auth'] ?? null) &&
                file_exists("$servicesDir/auth.php"),
            'export' => is_string($app['export'] ?? null) &&
                file_exists("$servicesDir/export.php"),
            'secret' => is_string($app['secret'] ?? null) &&
                file_exists("$servicesDir/secret.php"),
            'template' => is_string($app['ui']['template'] ?? null) &&
                file_exists("$servicesDir/template.php"),
        ];

        if ($defined['auth']) {
            $auths = require "$servicesDir/auth.php";
            if (isset($auths[$app['auth']])) {
                $app['auth'] = $auths[$app['auth']];
            }
        }

        if ($defined['export']) {
            $exports = require "$servicesDir/export.php";
            if (isset($exports[$app['export']])) {
                $app['export'] = $exports[$app['export']];
            }
        }

        if ($defined['template']) {
            $templates = require "$servicesDir/template.php";
            $template = $app['ui']['template'];
            $app['ui']['template'] = [
                'name' => $template,
                'builder' => $templates[$template]['builder'],
            ];
            $dialogs = $templates[$template]['dialogs'] ?? null;
            if (is_array($dialogs)) {
                // Wait until the config file is loaded before applying these options,
                // and make sure the options are merged with the existing.
                $jaxon->callback()->boot(fn() =>
                    $jaxon->setAppOptions($dialogs, 'dialogs.default'));
            }
        }

        if ($defined['secret']) {
            $secrets = require "$servicesDir/secret.php";
            if (isset($secrets[$app['secret']])) {
                $app['secret'] = $secrets[$app['secret']];
            }
        }

        return $app;
    }

    /**
     * @param array $app
     * @param string $configDir
     * @param Jaxon $jaxon
     *
     * @return array
     */
    private static function registerServices(array $app, string $configDir, Jaxon $jaxon): array
    {
        $app = self::appConfig($app, "$configDir/services", $jaxon);
        $services = [
            ...self::registerAuth($app['auth'] ?? null),
            ...self::registerExport($app['export'] ?? null),
            ...self::registerSecret($app['secret'] ?? [], $jaxon),
            ...self::registerUiBuilder($app['ui']['template'] ?? [], $jaxon),
        ];
        $jaxon->setAppOptions($services, 'container.set');

        return $app;
    }
}
