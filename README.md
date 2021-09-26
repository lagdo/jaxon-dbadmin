A database admin dashboard based on Jaxon and Adminer
=====================================================

This package is based on [Adminer](https://github.com/vrana/adminer).

It inserts a database admin dashboard into an existing PHP application.
Thanks to the [Jaxon library](https://www.jaxon-php.org), it installs and runs in a page of the application.
All its operations are performed with Ajax requests.

Howtos
------

This blog post on the `Jaxon` website explains how to install `Jaxon Adminer` on [Voyager](https://voyager-docs.devdojo.com), an admin panel based on the `Laravel` framework: [In english](https://www.jaxon-php.org/blog/2021/03/install-jaxon-adminer-on-voyager.html), and [in french](https://www.jaxon-php.org/blog/2021/03/installer-jaxon-adminer-dans-voyager.html).

Documentation
-------------

Install the jaxon library so it bootstraps from a config file and handles ajax requests. Here's the [documentation](https://www.jaxon-php.org/docs/v3x/advanced/bootstrap.html).

Install this package with Composer. If a [Jaxon plugin](https://www.jaxon-php.org/docs/v3x/plugins/frameworks.html) exists for your framework, you can also install it. It will automate the previous step.

Install the drivers packages for the databases servers you need to manage.
The following drivers are available:
- MySQL: [https://github.com/lagdo/dbadmin-driver-mysql](https://github.com/lagdo/dbadmin-driver-mysql)
- PostgreSQL: [https://github.com/lagdo/dbadmin-driver-pgsql](https://github.com/lagdo/dbadmin-driver-mysql)
- Sqlite: [https://github.com/lagdo/dbadmin-driver-sqlite](https://github.com/lagdo/dbadmin-driver-sqlite)

Declare the package and the database servers in the `app` section of the [Jaxon configuration file](https://www.jaxon-php.org/docs/v3x/advanced/bootstrap.html).

See the corresponding package for specific database server options.

```php
    'app' => [
        // Other config options
        // ...
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
                'servers' => [
                    // The database servers
                ],
            ],
        ],
    ],
```

Insert the CSS and javascript codes in the HTML pages of your application using calls to `jaxon()->getCss()` and `jaxon()->getScript(true)`.

In the page that displays the dashboard, insert the HTML code returned by the call to `jaxon()->package(\Lagdo\DbAdmin\Package::class)->getHtml()`. Two cases are then possible.

- If the dashboard is displayed on a dedicated page, make a call to `jaxon()->package(\Lagdo\DbAdmin\Package::class)->ready()` in your PHP code when loading the page.

- If the dashboard is loaded with an Ajax request in a page already displayed, execute the javascript code returned the call to `jaxon()->package(\Lagdo\DbAdmin\Package::class)->getReadyScript()` after the page is loaded.

Additional config options
-------------------------

There are other config options that can be used to customize `Jaxon Adminer` operation.

The `default` option sets a database server `Jaxon Adminer` must connect to when it starts.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
                'servers' => [
                    // The database servers
                ],
                'default' => 'server_id',
            ],
        ],
    ],
```

The `access` options restrict access only to databases or a defined set of databases on any server.
If the `access.server` is set to `false` at package level, then the access to all servers information will be forbidden.
The `access.server` option can also be set at a server level, and in this case it applies only to that specific server.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
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
In the above configuration, the user will be able to access server information only on the `second_server`.

The `access.databases` and `access.schemas` options define the set of databases and schemas the user can access.
This options can only be defined at server level, and will apply to that specific server.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
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
In the above configuration, the user will be able to get access only to three databases on the `second_server`, while he will have full access to the `first_server`.

Data import
-----------

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

Data export
-----------

Databases can also be exported to various types of files: SQL, CSV, and more.
A directory where the exported files are going to be saved must then be defined in the configuration, as well as an url where they can be downloaded.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
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
The web server needs to be setup to serve the files in the `dir` from `url`.

Change the UI framework
-----------------------

Starting from version 0.6, this package is designed to support multiple UI frameworks, and multiple templates.

The current template is set using the `template` option, and it default value is `bootstrap3`.

```php
    'app' => [
        'packages' => [
            Lagdo\DbAdmin\Package::class => [
                'template' => 'bootstrap3',
                'servers' => [
                    // The database servers
                ],
            ],
        ],
    ],
```

The following UI frameworks are supported:

- [Bootstrap 3](https://getbootstrap.com/) (`bootstrap3`)
- [Bootstrap 4](https://getbootstrap.com/) (`bootstrap4`)

More UI frameworks will be added in future releases.

Contribute
----------

- Issue Tracker: github.com/lagdo/jaxon-adminer/issues
- Source Code: github.com/lagdo/jaxon-adminer

License
-------

The project is licensed under the Apache license.
