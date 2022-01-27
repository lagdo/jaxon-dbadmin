<?php

$di = \jaxon()->di();
// Register the translator in the dependency container
$di->auto(Lagdo\DbAdmin\Db\Translator::class);
$di->alias(Lagdo\DbAdmin\Driver\TranslatorInterface::class, Lagdo\DbAdmin\Db\Translator::class);

// Register the input in the dependency container
$di->auto(Lagdo\DbAdmin\Driver\Input::class);

// Register the db classes and aliases in the dependency container
$di->auto(Lagdo\DbAdmin\Db\Util::class);
$di->alias(Lagdo\DbAdmin\Driver\UtilInterface::class, Lagdo\DbAdmin\Db\Util::class);
$di->auto(Lagdo\DbAdmin\Db\Admin::class);

// Selected database driver
$di->set(Lagdo\DbAdmin\Driver\DriverInterface::class, function($di) {
    // The key below is defined by the corresponding plugin package.
    return $di->get('dbadmin_driver_' . $di->get('dbadmin_config_driver'));
});

// Register the template builders
$di->auto(Lagdo\DbAdmin\Ui\Builder::class);

// Selected HTML template builder
$di->set(Lagdo\UiBuilder\BuilderInterface::class, function($di) {
    // The key below is defined by the corresponding plugin package.
    return $di->get('dbadmin_builder_' . $di->get('dbadmin_config_builder'));
});
