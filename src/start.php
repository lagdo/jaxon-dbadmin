<?php

$di = \jaxon()->di();
// Register the translator in the dependency container
$di->auto(Lagdo\DbAdmin\Translator::class);

// Register the db classes and aliases in the dependency container
$di->auto(Lagdo\DbAdmin\Db\Util::class);
$di->alias(Lagdo\DbAdmin\Driver\UtilInterface::class, Lagdo\DbAdmin\Db\Util::class);

// Database specific classes
$di->set(Lagdo\DbAdmin\Driver\DriverInterface::class, function($di) {
    // The key below is defined by the corresponding plugin package.
    return $di->get('adminer_driver_' . $di->get('adminer_config_driver'));
});
$di->set(Lagdo\DbAdmin\Driver\Db\ServerInterface::class, function($di) {
    $driver = $di->get(Lagdo\DbAdmin\Driver\DriverInterface::class);
    return $driver->server();
});
$di->set(Lagdo\DbAdmin\Driver\Db\TableInterface::class, function($di) {
    $driver = $di->get(Lagdo\DbAdmin\Driver\DriverInterface::class);
    return $driver->table();
});
$di->set(Lagdo\DbAdmin\Driver\Db\QueryInterface::class, function($di) {
    $driver = $di->get(Lagdo\DbAdmin\Driver\DriverInterface::class);
    return $driver->query();
});
$di->set(Lagdo\DbAdmin\Driver\Db\GrammarInterface::class, function($di) {
    $driver = $di->get(Lagdo\DbAdmin\Driver\DriverInterface::class);
    return $driver->grammar();
});
$di->set(Lagdo\DbAdmin\Driver\Db\ConnectionInterface::class, function($di) {
    $driver = $di->get(Lagdo\DbAdmin\Driver\DriverInterface::class);
    return $driver->connection();
});
