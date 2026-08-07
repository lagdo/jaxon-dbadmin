[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lagdo/jaxon-dbadmin/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/lagdo/jaxon-dbadmin/?branch=main)
[![StyleCI](https://styleci.io/repos/402856578/shield?branch=main)](https://styleci.io/repos/402856578)

[![Latest Stable Version](https://poser.pugx.org/lagdo/jaxon-dbadmin/v/stable)](https://packagist.org/packages/lagdo/jaxon-dbadmin)
[![Total Downloads](https://poser.pugx.org/lagdo/jaxon-dbadmin/downloads)](https://packagist.org/packages/lagdo/jaxon-dbadmin)
[![License](https://poser.pugx.org/lagdo/jaxon-dbadmin/license)](https://packagist.org/packages/lagdo/jaxon-dbadmin)

A database admin dashboard based on Jaxon and Adminer
=====================================================

Jaxon DbAdmin is a complete rewrite of [Adminer](https://github.com/vrana/adminer), the popular database admin dashboard.

It inserts a database admin dashboard into an existing PHP application.
Thanks to the [Jaxon library](https://www.jaxon-php.org), it installs and runs in a page of the application.
All its operations are performed with Ajax requests.

The database access code (and thus the provided features) originates from [Adminer](https://github.com/vrana/adminer). The original code was refactored to take advantage of the latest PHP features (namespaces, interfaces, DI, and so on), and separated into multiple Composer packages.

Here's the monorepo where the packages are developed: [https://github.com/lagdo/dbadmin-mono](https://github.com/lagdo/dbadmin-mono).

## Features and current status

This application and the related packages are still being actively developed, and the provided features are still basic and need improvements.

The following features are currently available:
- Browse servers and databases in multiple tabs.
- Open the query editor in multiple tabs, with query text retention.
- Save the current tabs in user preferences.
- Save and show the query history.
- Save queries in user favorites.
- Read database options with an extensible config reader.
- Read database credentials from a secret manager. Currently supported:
  - [Infisical](https://infisical.com/)
  - [AWS Secrets Manager](https://aws.amazon.com/secrets-manager/)
  - [GCP Secret Manager](https://cloud.google.com/security/products/secret-manager)
  - [OpenBao](https://openbao.org) (compatible with [HashiCorp Vault](https://www.hashicorp.com/fr/products/vault))
- Show tables and views details.
- Query a table.
- Query a view.
- Execute queries in the query editor.
- Use a better editor for SQL queries.
- Import or export data.
- Insert, modify or delete data in a table.
- Create or drop a database.
- Create or alter a table or view.
- Drop a table or view.

The following features are not yet implemented, and planned for future releases:
- Navigate through related tables.
- Code completion for table and field names in the SQL editor.
- An advanced GUI-based query builder.
- Automated tests.
- Support more secret managers.
  - [MS Azure Key Vault](https://azure.microsoft.com/products/key-vault)
  - [Alibaba KMS](https://www.alibabacloud.com/en/product/kms)

Howtos
------

This blog post on the `Jaxon` website explains how to install `Jaxon DbAdmin` on [Backpack](https://backpackforlaravel.com), an admin panel based on the `Laravel` framework: [https://www.jaxon-php.org/blog/2025/07/install-jaxon-dbadmin-on-backpack.html](https://www.jaxon-php.org/blog/2025/07/install-jaxon-dbadmin-on-backpack.html).

### The Jaxon DbAdmin application

The [https://github.com/lagdo/dbadmin-app](https://github.com/lagdo/dbadmin-app) repo provides a ready-to-use application, made with the [Laravel](https://laravel.com/) framework, and `Jaxon DbAdmin`.
<!-- The [https://github.com/lagdo/dbadmin-voyager](https://github.com/lagdo/dbadmin-voyager) repo provides a ready-to-use application, made with the [Voyager Admin](https://voyager.devdojo.com/) dashboard, and `Jaxon DbAdmin`. -->

The driver packages for [PostgreSQL](https://github.com/lagdo/dbadmin-driver-mysql), [MySQL](https://github.com/lagdo/dbadmin-driver-mysql) and [SQLite](https://github.com/lagdo/dbadmin-driver-sqlite) are included, so the user just need to add its databases in the config file.

A [Docker image](https://hub.docker.com/r/lagdo/jaxon-dbadmin) is also provided to get started easily.
It runs the [Laravel based DbAdmin application](https://github.com/lagdo/dbadmin-app).

Documentation
-------------

Install the Jaxon library so it bootstraps from a config file and handles ajax requests.
Here's the [documentation](https://www.jaxon-php.org/docs/v5x/about/configuration.html).

Install this package with Composer. If a [framework extension](https://www.jaxon-php.org/docs/v5x/integrations/about.html) is available for your framework, you must also install it. It will automate the previous step.

### The database drivers

Install the drivers packages for the database servers you need to manage.
The following drivers are available:
- PostgreSQL: [https://github.com/lagdo/dbadmin-driver-pgsql](https://github.com/lagdo/dbadmin-driver-mysql)
- MySQL: [https://github.com/lagdo/dbadmin-driver-mysql](https://github.com/lagdo/dbadmin-driver-mysql)
- Sqlite: [https://github.com/lagdo/dbadmin-driver-sqlite](https://github.com/lagdo/dbadmin-driver-sqlite)

Declare the package and the database servers in the `app.packages` section of the Jaxon configuration.
See the [Jaxon packages documentation](https://www.jaxon-php.org/docs/v5x/extensions/packages.html).

See the corresponding database driver package for specific database server options.

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
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

### The CSS and javascript codes

Insert the CSS and javascript codes in the HTML page of your application, as described in the [Jaxon documentation](https://www.jaxon-php.org/docs/v5x/registrations/javascript.html).

### The UI builder

This package uses the [HTML UI builder](https://github.com/lagdo/ui-builder) to build UI components for various frontend frameworks.

The packages for the UI framework in use must also be installed.
The following builders are available:
- Bootstrap 5: [https://github.com/lagdo/ui-builder-bootstrap5](https://github.com/lagdo/ui-builder-bootstrap5)
- Bootstrap 4: [https://github.com/lagdo/ui-builder-bootstrap4](https://github.com/lagdo/ui-builder-bootstrap4)
- Bootstrap 3: [https://github.com/lagdo/ui-builder-bootstrap3](https://github.com/lagdo/ui-builder-bootstrap3)

In the above example, the UI will be built with Bootstrap5 components.

```php
    'app' => [
        'ui' => [
            'template' => 'bootstrap5',
        ],
    ],
```

Additional config options
-------------------------

There are other config options that can be used to customize the `Jaxon DbAdmin` operation.

The `default` option sets a database server `Jaxon DbAdmin` must connect to when it starts, instead of displaying a blank page.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
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
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
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
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
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

The app admin may need to customize the access parameters, depending for example on the connected user account or role.

In this case, the `provider` option can be used to define a callable that returns the access options as an array, which will then be used to configure the package.

The defined options are passed to the callable, so it can be used as a basis to build the customized config.

```php
$dbConfigProvider = function(array $config) {
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
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
                // A callable that return the access options.
                'provider' => $dbConfigProvider,
                'servers' => [],
                'default' => 'server_mysql',
                'access' => [
                    'server' => false,
                ],
            ],
        ],
    ],
```

### Audit logs

The queries executed by the users can be saved in a provided database.

The required options are provided under the `queries` key.

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
                // ...
                'queries' => [
                    'record' => [
                        'builder' => [
                            'enabled' => true,
                        ],
                        'editor' => [
                            'enabled' => true,
                        ],
                    ],
                    'admin' => [
                        'history' => [
                            'show' => true,
                            'distinct' => true,
                            'limit' => 10,
                        ],
                        'favorite' => [
                            'show' => true,
                            'limit' => 10,
                        ],
                    ],
                    'database' => [
                        // Same as the "servers" items, but "name" is the database name.
                        'driver' => 'pgsql',
                        'host' => '',
                        'port' => 0,
                        'username' => '',
                        'password' => '',
                        'name' => 'auditdb', // The database name
                        'schema' => '', // Optionnally, the schema name
                    ],
                ],
            ],
        ],
    ],
```

The `queries.database` options identify the database where the queries are saved.
Depending on the database server, one of the scripts in the `migrations/pgsql`, `migrations/mysql` or `migrations/sqlite` directories in this repository must be used to create the audit database.

The `queries.record` options indicate which queries are saved.
Recording the queries executed in the query `builder` or the query `editor` can be enabled or disabled here.

When the `queries.admin.history.show` and `queries.admin.favorite.show` options are set to true, the `History` and `Favorites`   tabs are displayed in the query editor page.
When the query history is enabled, the `queries.admin.history.distinct` option enables the removal of duplicates in the listed queries, while the `queries.admin.history.limit` option sets the max number of queries for pagination.

### The DbAudit package

Jaxon DbAdmin includes a second package which can be use to display the audit logs in another web page.
The `Lagdo\DbAdmin\App\DbAuditPackage` needs to be provided with the audit database connection parameters.

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\DbAuditPackage::class => [
                // ...
                'audit' => [
                    'enabled' => true,
                ],
                'database' => [
                    // Same as the "servers" items, but "name" is the database name.
                    'driver' => 'pgsql',
                    'host' => '',
                    'port' => 0,
                    'username' => '',
                    'password' => '',
                    'name' => 'auditdb', // The database name
                    'schema' => '', // Optionnally, the schema name
                ],
            ],
        ],
    ],
```

Generally, the access to the audit logs page will be reserved for a selected list of users.
While the corresponding options can also be set here, their usage is out of the scope of the package.

### The database config readers

Jaxon DbAdmin uses an extensible `config reader` to read the database credentials.
By default, the database credentials are stored in a `json`, `yaml` or `php` config file.
Jaxon DbAdmin provides a [default `config reader`](https://github.com/lagdo/jaxon-dbadmin/src/Provider/Config/ServerConfigProvider.php) which is able to read these values, either from the config file content, or from environment variables.

An alternative `config reader` can be specified in the package config options.
Let say for example the `CustomServerConfigProvider` class inherits from the default `config reader` and redefines some functions.
The `DbAdminPackage` and `DbAuditPackage` can be configured to use it as their `config reader`.

```php
    'app' => [
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
                // Read the database credentials with the custom config reader.
                'reader' => [
                    'server' => CustomServerConfigProvider::class,
                ],
                // ...
            ],
            Lagdo\DbAdmin\App\DbAuditPackage::class => [
                // Read the database credentials with the custom config reader.
                'reader' => [
                    'server' => CustomServerConfigProvider::class,
                ],
                // ...
            ],
        ],
    ],
```

In addition to the `config reader`, a custom `credential reader` can also be provided, and used to read database usernames and passwords from various secret managers.

Jaxon DbAdmin includes a `config reader` for reading database credentials from an [Infisical server](https://infisical.com).
The setup of the required `Secrets Management` project in the Infisical server is described here: [https://www.jaxon-php.org/blog/2026/01/secure-the-jaxon-dbadmin-database-credentials-with-infisical.html](https://www.jaxon-php.org/blog/2026/01/secure-the-jaxon-dbadmin-database-credentials-with-infisical.html).

The Infisical `config reader` needs to be provided with a closure which returns the key where to find each secret in the server.

```php
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Secret\InfisicalConfigProvider;

$secretKetBuilder = function(string $prefix, string $option, AuthInterface $auth) {
    // Select a secret key based on the authenticated user, and the option prefix and name.
    return $secretKey;
};
$reader = jaxon()->di()->g(InfisicalConfigProvider::class);
$reader->setSecretKeyBuilder($secretKeyBuilder);
```

The packages can then be configured to use the Infisical `config reader`.

```php
use Lagdo\DbAdmin\Support\Provider\Config\ServerConfigProvider;
use Lagdo\DbAdmin\Support\Provider\Secret\InfisicalConfigProvider;

    'app' => [
        // ...
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
                // Read the database credentials with the custom config reader.
                'reader' => [
                    'server' => ServerConfigProvider::class,
                    'access' => InfisicalConfigProvider::class,
                ],
                // ...
            ],
            Lagdo\DbAdmin\App\DbAuditPackage::class => [
                // Read the database credentials with the custom config reader.
                'reader' => [
                    'server' => ServerConfigProvider::class,
                    'access' => InfisicalConfigProvider::class,
                ],
                // ...
            ],
        ],
    ],
```

### Data export

Databases can be exported to various types of files: SQL, CSV, and more.

The export feature is configured with two callbacks.

The `writer` callback saves the export data content in a file. It takes the content and the file name as parameters, and returns the URI to the created file.
It must return an empty string in case of error.

The `reader` callback takes an export file name as parameter, then reads and returns its content.
The web app must be configured to call the `reader` callback and send back the returned content when an HTTP request hits the URI returned by the `writer` callback.

Both callbacks can use the [Jaxon Storage](https://github.com/jaxon-php/jaxon-storage), as in the example below, to read and write the exported files, which can then be saved on different types of filesystems, thanks to the [Flysystem](https://flysystem.thephpleague.com) library.

The callbacks can also save the file in different locations, depending for example on the application user.

```php
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use function Jaxon\storage;

    'app' => [
        'storage' => [
            'stores' => [
                'exports' => [
                    'adapter' => 'local',
                    'dir' => "/path/to/exports",
                ],
            ],
        ],
        'packages' => [
            Lagdo\DbAdmin\App\DbAdminPackage::class => [
                'servers' => [
                    // The database servers
                ],
                'export' => [
                    'writer' => function(string $content, string $filename): string {
                        try {
                            // Make a Filesystem object with the storage.exports options.
                            $storage = storage()->get('exports');
                            $storage->write($filename, "$content\n");
                        } catch (FilesystemException|UnableToWriteFile) {
                            return '';
                        }
                        // Return the link to the exported file.
                        return "/export.php?file=$filename";
                    },
                    'reader' => function(string $filename): string {
                        try {
                            // Make a Filesystem object with the storage.exports options.
                            $storage = storage()->get('exports');
                            return !$storage->fileExists($filename) ?
                                "No file $filename found." : $storage->read($filename);
                        } catch (FilesystemException|UnableToReadFile) {
                            return "No file $filename found.";
                        }
                    },
                ],
            ],
        ],
    ],
```

### Data import (with file upload)

SQL files can be uploaded and executed on a server. This feature is implemented using the [Jaxon ajax upload](https://github.com/jaxon-php/jaxon-upload) and [Jaxon Storage](https://github.com/jaxon-php/jaxon-storage) packages, which then needs to be configured in the `Jaxon` config file.

```php
    'app' => [
        'storage' => [
            'stores' => [
                'uploads' => [
                    'adapter' => 'local',
                    'dir' => '/path/to/the/upload/dir',
                ],
            ],
        ],
        'upload' => [
            'enabled' => true,
            'files' => [
                'sql_files' => [
                    'storage' => 'uploads',
                ],
            ],
        ],
    ],
```

In this example, `sql_files` is the `name` attribute of the file upload field, and of course `/path/to/the/upload/dir` needs to be writable.
Other parameters can also be defined to limit the size of the uploaded files or retrict their extensions or mime types.
the [Jaxon ajax upload documentation](https://www.jaxon-php.org/docs/v5x/features/upload.html)

Contribute
----------

- Issue Tracker: github.com/lagdo/jaxon-dbadmin/issues
- Source Code: github.com/lagdo/jaxon-dbadmin

License
-------

Jaxon DbAdmin is licensed under the Business Source License 1.1.

The software can be used internally, including in commercial
organizations. Providing Jaxon DbAdmin as a hosted, managed, or SaaS
service requires a separate commercial license.

After the Change Date specified in the LICENSE file, the applicable
version will be available under Apache License 2.0.
