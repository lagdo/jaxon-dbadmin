<?php

$di = \jaxon()->di();
// Register the translator in the dependency container
$di->auto(Lagdo\DbAdmin\Db\Translator::class);
$di->alias(Lagdo\DbAdmin\Driver\TranslatorInterface::class, Lagdo\DbAdmin\Db\Translator::class);

// Register the input in the dependency container
$di->auto(Lagdo\DbAdmin\Driver\Input::class);
$di->alias(Lagdo\DbAdmin\Driver\InputInterface::class, Lagdo\DbAdmin\Driver\Input::class);

// Register the db classes and aliases in the dependency container
$di->auto(Lagdo\DbAdmin\Db\Util::class);
$di->alias(Lagdo\DbAdmin\Driver\UtilInterface::class, Lagdo\DbAdmin\Db\Util::class);
$di->auto(Lagdo\DbAdmin\Db\Admin::class);

// Database specific classes
$di->set(Lagdo\DbAdmin\Driver\DriverInterface::class, function($di) {
    // The key below is defined by the corresponding plugin package.
    return $di->get('adminer_driver_' . $di->get('adminer_config_driver'));
});
