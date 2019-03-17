# OkvpnMigrationBundle
Database structure and data manipulator.

[![Build Status](https://travis-ci.org/okvpn/migration-bundle.svg?branch=master)](https://travis-ci.org/okvpn/migration-bundle)
[![Latest Stable Version](https://poser.okvpn.org/okvpn/migration-bundle/version)](https://packagist.org/packages/okvpn/migration-bundle)
[![Total Downloads](https://poser.okvpn.org/okvpn/migration-bundle/downloads)](https://packagist.org/packages/okvpn/migration-bundle)
[![Latest Unstable Version](https://poser.okvpn.org/okvpn/migration-bundle/v/unstable)](//packagist.org/packages/okvpn/migration-bundle)
[![License](https://poser.okvpn.org/okvpn/migration-bundle/license)](https://packagist.org/packages/okvpn/migration-bundle)

![Migration cast](http://poser.okvpn.org/images/migrationcast.svg)

Intro
-------

OkvpnMigrationBundle allow write database migrations using database agnostic PHP code,
which uses the external [doctrine/dbal][7] library Doctrine Schema Manager.

```php
<?php // src/Migrations/Schema/v1_5

namespace App\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class AppMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('meteo');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('timestamp', 'datetime');
        $table->addColumn('temp', 'decimal', ['precision' => 4, 'scale' => 2]);
        $table->addColumn('pressure', 'decimal', ['precision' => 6, 'scale' => 2]);
        $table->addColumn('humidity', 'decimal', ['precision' => 4, 'scale' => 2]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['timestamp']);
    }
}
```

Features
--------
- Write database migrations using database agnostic PHP code.
- Locate migrations inside each bundle and supports the multiple locations.
- Compatible with different versions of Doctrine and Symfony 3-4.
- Extensions for database structure migrations.
- Events before and after migrations.

Installations
-------------

Install using composer:
```
composer require okvpn/migration-bundle
```

If you don't use Symfony Flex, you must enable the bundle manually in the application:

Symfony 4 `config/bundles.php`
```php
<?php
return [
    Okvpn\Bundle\MigrationBundle\OkvpnMigrationBundle::class => ['all' => true],
    //...
];

```

Symfony 2 - 3, enable the bundle in `app/AppKernel.php`
```php
<?php

use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Okvpn\Bundle\MigrationBundle\OkvpnMigrationBundle(),
            //...
        ];
    }
}
```

Database structure migrations
-----------------------------

Each bundle can have migration files that allow to update database schema.

Migration files should be located in `Migrations\Schema\version_number` folder. A version number must be an PHP-standardized version number string, but with some limitations. This string must not contain "." and "+" characters as a version parts separator. More info about PHP-standardized version number string can be found in [PHP manual][1].

Each migration class must implement [Migration](./Migration/Migration.php) interface and must implement `up` method. This method receives a current database structure in `schema` parameter and `queries` parameter which can be used to add additional queries.

With `schema` parameter, you can create or update database structure without fear of compatibility between database engines.
If you want to execute additional SQL queries before or after applying a schema modification, you can use `queries` parameter. This parameter represents a [query bag](./Migration/QueryBag.php) and allows to add additional queries which will be executed before (`addPreQuery` method) or after (`addQuery` or `addPostQuery` method). A query can be a string or an instance of a class implements [MigrationQuery](./Migration/MigrationQuery.php) interface. There are several ready to use implementations of this interface:

 - [SqlMigrationQuery](./Migration/SqlMigrationQuery.php) - represents one or more SQL queries
 - [ParametrizedSqlMigrationQuery](./Migration/ParametrizedSqlMigrationQuery.php) - similar to the previous class, but each query can have own parameters.

If you need to create own implementation of [MigrationQuery](./Migration/MigrationQuery.php) the [ConnectionAwareInterface](./Migration/ConnectionAwareInterface.php) can be helpful. Just implement this interface in your migration query class if you need a database connection. Also you can use [ParametrizedMigrationQuery](./Migration/ParametrizedMigrationQuery.php) class as a base class for your migration query.

If you have several migration classes within the same version and you need to make sure that they will be executed in a specified order you can use [OrderedMigrationInterface](./Migration/OrderedMigrationInterface.php) interface.

Example of migration file:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class AcmeTestBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('test_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('field', 'string', ['length' => 500]);
        $table->addColumn('another_field', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'old_table_name',
            'new_table_name'
        );
        $queries->addQuery(
            "ALTER TABLE another_table ADD COLUMN test_column INT NOT NULL",
        );
    }
}

```


Each bundle can have an **installation** file as well. This migration file replaces running multiple migration files. Install migration class must implement [Installation](./Migration/Installation.php) interface and must implement `up` and `getMigrationVersion` methods. The `getMigrationVersion` method must return max migration version number that this installation file replaces.

During an install process (it means that you installs a system from a scratch), if install migration file was found, it will be loaded first and then migration files with versions greater then a version returned by `getMigrationVersion` method will be loaded.

For example. We have `v1_0`, `v1_1`, `v1_2`, `v1_3` migrations. And additionally, we have install migration class. This class returns `v1_2` as a migration version. So, during an install process the install migration file will be loaded and then only `v1_3` migration file will be loaded. Migrations from `v1_0` to `v1_2` will not be loaded.

Example of install migration file:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Installation;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeTestBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('test_installation_table');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('field', 'string', ['length' => 500]);
        $table->setPrimaryKey(['id']);
    }
}

```

To run migrations, there is **okvpn:migration:load** command. This command collects migration files from bundles, sorts them by version number and applies changes.

This command supports some additional options:

 - **force** - Causes the generated by migrations SQL statements to be physically executed against your database;
 - **dry-run** - Outputs list of migrations without apply them;
 - **show-queries** - Outputs list of database queries for each migration file;
 - **bundles** - A list of bundles to load data from. If option is not set, migrations will be taken from all bundles;
 - **exclude** - A list of bundle names which migrations should be skipped.

Also there is **okvpn:migration:dump** command to help in creation installation files. This command outputs current database structure as a plain sql or as `Doctrine\DBAL\Schema\Schema` queries.

This command supports some additional options:

 - **plain-sql** - Out schema as plain sql queries
 - **bundle** - Bundle name for which migration wll be generated
 - **migration-version** - Migration version number. This option will set the value returned by `getMigrationVersion` method of generated installation file.
  
Good practice for bundle is to have installation file for current version and migration files for migrating from previous versions to current.

Next algorithm may be used for new versions of your bundle:

 - Create new migration
 - Apply it with **okvpn:migration:load**
 - Generate fresh installation file with **okvpn:migration:dump**
 - If required - add migration extensions calls to generated installation.


Extensions for database structure migrations
--------------------------------------------
Sometime you cannot use standard Doctrine methods for database structure modification. For example `Schema::renameTable` does not work because it drops existing table and then creates a new table. To help you to manage such case and allow to add some useful functionality to any migration a extensions mechanism was designed. The following example shows how [RenameExtension][5] can be used:
``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class AcmeTestBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'old_table_name',
            'new_table_name'
        );
    }
}
```
As you can see to use the [RenameExtension][5] your migration class should implement [RenameExtensionAwareInterface][6] and `setRenameExtension` method.
Also there is some additional useful interfaces you can use in your migration class:

 - `ContainerAwareInterface` - provides an access to Symfony dependency container
 - [DatabasePlatformAwareInterface][3] - allows to write a database type independent migrations
 - [NameGeneratorAwareInterface][4] - provides an access to [DbIdentifierNameGenerator](./Tools/DbIdentifierNameGenerator.php) class which can be used to generate names of indices, foreign key constraints and others.

Create own extensions for database structure migrations
-------------------------------------------------------
To create your own extension you need too do the following simple steps:

 - Create an extension class in `YourBundle/Migration/Extension` directory. Using `YourBundle/Migration/Extension` directory is not mandatory, but highly recommended. For example:
``` php
<?php

namespace Acme\Bundle\TestBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class MyExtension
{
    public function doSomething(Schema $schema, QueryBag $queries, /* other parameters, for example */ $tableName)
    {
        $table = $schema->getTable($tableName); // highly recommended to make sure that a table exists
        $query = 'SOME SQL'; /* or $query = new SqlMigrationQuery('SOME SQL'); */

        $queries->addQuery($query);
    }
}
```
 - Create `*AwareInterface` in the same namespase. It is important that the interface name should be `{ExtensionClass}AwareInterface` and set method should be `set{ExtensionClass}({ExtensionClass} ${extensionName})`. For example:
``` php
<?php

namespace Acme\Bundle\TestBundle\Migration\Extension;

/**
 * MyExtensionAwareInterface should be implemented by migrations that depends on a MyExtension.
 */
interface MyExtensionAwareInterface
{
    /**
     * Sets the MyExtension
     *
     * @param MyExtension $myExtension
     */
    public function setMyExtension(MyExtension $myExtension);
}
```
 - Register an extension in dependency container. For example
``` yaml
parameters:
    acme_test.migration.extension.my.class: Acme\Bundle\TestBundle\Migration\Extension\MyExtension

services:
    acme_test.migration.extension.my:
        class: %acme_test.migration.extension.my.class%
        tags:
            - { name: okvpn_migration.extension, extension_name: test /*, priority: -10 - priority attribute is optional an can be helpful if you need to override existing extension */ }
```

If you need an access to the database platform or the name generator you extension class should implement [DatabasePlatformAwareInterface][3] or [NameGeneratorAwareInterface][4] appropriately.
Also if you need to use other extension in your extension the extension class should just implement `*AwareInterface` of the extension you need.


  [1]: http://php.net/manual/en/function.version-compare.php
  [2]: https://github.com/doctrine/data-fixtures#fixture-ordering
  [3]: ./Migration/Extension/DatabasePlatformAwareInterface.php
  [4]: ./Migration/Extension/NameGeneratorAwareInterface.php
  [5]: ./Migration/Extension/RenameExtension.php
  [6]: ./Migration/Extension/RenameExtensionAwareInterface.php
  [7]: https://www.doctrine-project.org/projects/doctrine-dbal/en/2.9/reference/schema-manager.html
