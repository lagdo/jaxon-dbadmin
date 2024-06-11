[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lagdo/jaxon-dbadmin/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/lagdo/jaxon-dbadmin/?branch=main)
[![StyleCI](https://styleci.io/repos/402856578/shield?branch=main)](https://styleci.io/repos/402856578)
[![Build Status](https://api.travis-ci.com/lagdo/jaxon-dbadmin.svg?branch=main)](https://app.travis-ci.com/github/lagdo/jaxon-dbadmin)
[![Coverage Status](https://coveralls.io/repos/github/lagdo/jaxon-dbadmin/badge.svg?branch=main)](https://coveralls.io/github/lagdo/jaxon-dbadmin?branch=main)

[![Latest Stable Version](https://poser.pugx.org/lagdo/jaxon-dbadmin/v/stable)](https://packagist.org/packages/lagdo/jaxon-dbadmin)
[![Total Downloads](https://poser.pugx.org/lagdo/jaxon-dbadmin/downloads)](https://packagist.org/packages/lagdo/jaxon-dbadmin)
[![License](https://poser.pugx.org/lagdo/jaxon-dbadmin/license)](https://packagist.org/packages/lagdo/jaxon-dbadmin)

A database admin dashboard based on Jaxon and Adminer
=====================================================

This package is based on [Adminer](https://github.com/vrana/adminer).

It inserts a database admin dashboard into an existing PHP application.
Thanks to the [Jaxon library](https://www.jaxon-php.org), it installs and runs in a page of the application.
All its operations are performed with Ajax requests.

Howtos
------

This blog post on the `Jaxon` website explains how to install `Jaxon DbAdmin` on [Voyager](https://voyager-docs.devdojo.com), an admin panel based on the `Laravel` framework: [In english](https://www.jaxon-php.org/blog/2021/03/install-jaxon-adminer-on-voyager.html), and [in french](https://www.jaxon-php.org/blog/2021/03/installer-jaxon-adminer-dans-voyager.html).

### Jaxon DbAdmin and Voyager

The [https://github.com/lagdo/dbadmin-voyager](https://github.com/lagdo/dbadmin-voyager) repo provides a ready-to-use package, made with the [Voyager Admin](https://voyager.devdojo.com/) dashboard, and `Jaxon DbAdmin` included.

The driver packages for [PostgreSQL](https://github.com/lagdo/dbadmin-driver-mysql), [MySQL](https://github.com/lagdo/dbadmin-driver-mysql) and [SQLite](https://github.com/lagdo/dbadmin-driver-sqlite) are also installed, so the user just need to add its databases in the config file.

Documentation
-------------

Install the Jaxon library so it bootstraps from a config file and handles ajax requests.
Here's the [documentation](https://www.jaxon-php.org/docs/v3x/advanced/bootstrap.html).

Install this package with Composer. If a [Jaxon plugin](https://www.jaxon-php.org/docs/v3x/plugins/frameworks.html) exists for your framework, you can also install it. It will automate the previous step.

### The database drivers

Install the drivers packages for the database servers you need to manage.
The following drivers are available:
- MySQL: [https://github.com/lagdo/dbadmin-driver-mysql](https://github.com/lagdo/dbadmin-driver-mysql)
- PostgreSQL: [https://github.com/lagdo/dbadmin-driver-pgsql](https://github.com/lagdo/dbadmin-driver-mysql)
- Sqlite: [https://github.com/lagdo/dbadmin-driver-sqlite](https://github.com/lagdo/dbadmin-driver-sqlite)

Declare the package and the database servers in the `app` section of the [Jaxon configuration file](https://www.jaxon-php.org/docs/v3x/advanced/bootstrap.html).

See the corresponding database driver package for specific database server options.

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'servers' => [
                    // The database servers
                    'pgsql_server' => [ // A unique identifier for this server
                        'driver' => 'pgsql',
                        'name' => '',     // The name to be displayed in the dashboard UI.
                        'host' => '',     // The database host name or address.
                        'port' => 0,      // The database port. Optional.
                        'username' => '', // The database user credentials.
                        'password' => '', // The database user credentials.
                    ],
                    'mysql_server' => [ // A unique identifier for this server
                        'driver' => 'mysql',
                        'name' => '',     // The name to be displayed in the dashboard UI.
                        'host' => '',     // The database host name or address.
                        'port' => 0,      // The database port. Optional.
                        'username' => '', // The database user credentials.
                        'password' => '', // The database user credentials.
                    ],
                ],
            ],
        ],
    ],
```

Insert the CSS and javascript codes in the HTML pages of your application using calls to `Jaxon\jaxon()->getCss()` and `Jaxon\jaxon()->getScript(true)`.

In the page that displays the dashboard, insert the HTML code returned by the call to `Jaxon\jaxon()->package(\Lagdo\DbAdmin\App\Package::class)->getHtml()`. Two cases are then possible.

- If the dashboard is displayed on a dedicated page, make a call to `Jaxon\jaxon()->package(\Lagdo\DbAdmin\App\Package::class)->ready()` in your PHP code when loading the page.

- If the dashboard is loaded with an Ajax request in a page already displayed, execute the javascript code returned the call to `Jaxon\jaxon()->package(\Lagdo\DbAdmin\App\Package::class)->getReadyScript()` after the page is loaded.

### The UI builder

This package uses the [HTML UI builder](https://github.com/lagdo/ui-builder) to build UI components for various frontend frameworks.
The packages for the UI framework in use must also be installed.
The following builders are available:
- Bootstrap 3, 4 and 5: [https://github.com/lagdo/ui-builder-bootstrap](https://github.com/lagdo/ui-builder-bootstrap)

In the above example, the UI will be built with Bootstrap3 components.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'template' => 'bootstrap3',
            ],
        ],
    ],
```

Additional config options
-------------------------

There are other config options that can be used to customize `Jaxon DbAdmin` operation.

The `default` option sets a database server `Jaxon DbAdmin` must connect to when it starts.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'servers' => [
                    // The database servers
                ],
                'default' => 'server_id',
            ],
        ],
    ],
```

### Access restriction

The `access` section provides a few options to restrict access to databases on any server.

If the `access.server` option is set to `false` at package level, then the access to all servers information will be forbidden, and the user will have access only to database contents.
The `access.server` option can also be set at a server level, and in this case it applies only to that specific server.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'servers' => [
                    // The database servers
                    'server_id' => [
                        // Database options
                        'access' => [
                            'server' => true,
                        ],
                    ],
                ],
                'default' => 'server_id',
                'access' => [
                    'server' => false,
                ],
            ],
        ],
    ],
```
In this configuration, the user will get access to server information only on the server with id `server_id`.

The `access.databases` and `access.schemas` options define the set of databases and schemas the user can access.
This options can only be defined at server level, and will apply to that specific server.
The `access.schemas` option will apply only on servers which provide that feature.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'servers' => [
                    // The database servers
                    'server_id' => [
                        // Database options
                        'access' => [
                            'server' => false,
                            'databases' => ['db1', 'db2', 'db3'],
                            'schemas' => ['public'],
                        ],
                    ],
                ],
                'default' => 'server_id',
            ],
        ],
    ],
```
In this configuration, the user will be able to get access only to three databases on the server with id `server_id`.

### Customizing the package config

The app admin may need to customize the access parameters, depending for example on the connected user account or group.

In this case, the `provider` option can be used to define a callable that returns the access options as an array,
which will then be used to configure the package.

The defined options are passed to the callable, so it can be used as a basis to build the customized config.

```php
$dbAdminOptionsGetter = function($config) {
    $config['servers']['server_mysql'] = [
        'driver' => 'mysql',
        'name' => '',     // The name to be displayed in the dashboard UI.
        'host' => '',     // The database host name or address.
        'port' => 0,      // The database port. Optional.
        'username' => '', // The database user credentials.
        'password' => '', // The database user credentials.
    ];
    $config['servers']['server_pgsql'] = [
        'driver' => 'pgsql',
        'name' => '',     // The name to be displayed in the dashboard UI.
        'host' => '',     // The database host name or address.
        'port' => 0,      // The database port. Optional.
        'username' => '', // The database user credentials.
        'password' => '', // The database user credentials.
    ];
    return $config;
};
```

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                // A callable that return the access options.
                'provider' => $dbAdminOptionsGetter,
                'template' => 'bootstrap3',
                'servers' => [],
                'default' => 'server_mysql',
                'access' => [
                    'server' => false,
                ],
            ],
        ],
    ],
```

### Debug console output

Starting from version `0.9.0`, the SQL queries that are executed can also be printed in the browser debug console,
if the `debug.queries` option is set to true.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'debug' => [
                    'queries' => true,
                ],
                'servers' => [
                    // The database servers
                ],
            ],
        ],
    ],
```

### Data import

SQL files can be uploaded and executed on a server. This feature is implemented using the [Jaxon ajax upload](https://www.jaxon-php.org/docs/v3x/registrations/upload.html) feature, which then needs to be configured in the `lib` section of the `Jaxon` config file.

```php
    'lib' => [
        'upload' => [
            'files' => [
                'sql_files' => [
                    'dir' => '/path/to/the/upload/dir',
                ],
            ],
        ],
    ],
```
As stated in the [Jaxon ajax upload documentation](https://www.jaxon-php.org/docs/v3x/registrations/upload.html), `sql_files` is the `name` attribute of the file upload field, and of course `/path/to/the/upload/dir` needs to be writable.
Other parameters can also be defined to limit the size of the uploaded files or retrict their extensions or mime types.

### Data export

Databases can also be exported to various types of files: SQL, CSV, and more.
A directory where the exported files are going to be saved must then be defined in the configuration, as well as an url where they can be downloaded.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\Package::class => [
                'servers' => [
                    // The database servers
                ],
                'export' => [
                    'dir' => '/path/to/the/export/dir',
                    'url' => 'http://www.domain.com/exports',
                ],
            ],
        ],
    ],
```
The web server needs to be setup to serve the files in the directory `dir` from url `url`.

Contribute
----------

- Issue Tracker: github.com/lagdo/jaxon-dbadmin/issues
- Source Code: github.com/lagdo/jaxon-dbadmin

License
-------

The project is licensed under the Apache license.
