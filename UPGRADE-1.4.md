# UPGRADE FROM 1.3 TO 1.4

1. Support for Sylius 2.3 and Symfony 8 has been added. Sylius 2.2 and Symfony 6.4/7.4 remain supported.

2. The plugin's service definitions have been migrated from XML to PHP, as Symfony 8 no longer supports XML service configuration.
   The `config/services.xml` file and the files in `config/services/` have been replaced with their `.php` counterparts.
   Service IDs and aliases have not changed. If you imported any of the plugin's service files directly,
   change the extension of the imported resource from `.xml` to `.php`.
