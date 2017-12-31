# From 1.1 to 1.2

## IMPORTANT DEPRECATED

The class `Okvpn\Bundle\MigrationBundle\Migration\DataFixturesExecutor, Okvpn\Bundle\MigrationBundle\Entity\DataFixture`, 
command `okvpn:migration:data:load` were marked as deprecated. Fixtures package will be moved to another separate repository

# From 1.2 to 2.0

* Changed minimum required php version to 7.0

## IMPORTANT BC Break

* Fixtures package was removed from migration bundle and moved to separate composer installable [package](https://github.com/okvpn/fixture-bundle)

#### Constructor signature was change:

- `Okvpn\Bundle\MigrationBundle\EventListener\PreUpMigrationListener`
- `Okvpn\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader`
- `Okvpn\Bundle\MigrationBundle\Tools\SchemaDiffDumper`
- `Okvpn\Bundle\MigrationBundle\Tools\SchemaDumper`
