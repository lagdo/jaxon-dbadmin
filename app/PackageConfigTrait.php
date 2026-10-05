<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Support\DiAlias\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Secret\KeyBuilderInterface;
use Lagdo\DbAdmin\Support\Service\Export\FileSystemInterface;
use Lagdo\UiBuilder\BuilderInterface;
use Closure;

use function count;
use function is_a;
use function is_string;
use function Jaxon\jaxon;
use function Lagdo\UiBuilder\Jaxon\initUiBuilder;
use function Lagdo\UiBuilder\Jaxon\uiRegister;

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
            is_string($definition) => fn(Container $di) => $di->make($definition),
            default => null,
        };
    }

    /**
     * @param array $services
     * @param array $secret
     *
     * @return void
     */
    private static function registerSecretProvider(array &$services, array $secret): void
    {
        if (!isset($secret['reader']) || !isset($secret['key'])) {
            $services[SecretConfigProvider::class] = fn() => new SecretConfigProvider();
            return;
        }

        jaxon()->setAppOption('container.alias.' .
            SecretConfigProvider::class, $secret['reader']);
        $services[KeyBuilderInterface::class] = self::serviceDefinition($secret['key']);
    }

    /**
     * @param array $services
     * @param array $template
     *
     * @return void
     */
    private static function registerUiBuilder(array &$services, array $template): void
    {
        jaxon()->setAppOption('template', $template['name']);
        $services[BuilderInterface::class] = static fn(Container $di) =>
            initUiBuilder($di->make($template['builder']));
    }

    /**
     * @param array $app
     *
     * @return void
     */
    private static function registerAppServices(array $app): void
    {
        uiRegister();

        $services = [];

        if (($auth = self::serviceDefinition($app['auth'] ?? null)) !== null) {
            $services[AuthInterface::class] = $auth;
        }
        if (($export = self::serviceDefinition($app['export'] ?? null)) !== null) {
            $services[FileSystemInterface::class] = $export;
        }

        self::registerSecretProvider($services, $app['secret'] ?? []);
        self::registerUiBuilder($services, $app['ui']['template'] ?? []);

        if (count($services) > 0) {
            jaxon()->setAppOptions($services, 'container.set');
        }
    }
}
