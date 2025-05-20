# SyliusWishlistPlugin

- [⬅️ Back](../README.md#overview)
- [➡️ Usage](./02-usage.md)

## Installation

This installation instruction assumes that you're using Symfony Flex. If you don't, take a look at the
[legacy installation instruction](legacy_installation.md). However, we strongly encourage you to use
Symfony Flex, it's much quicker!

1. Require plugin with composer:

    ```bash
    composer require sylius/wishlist-plugin
    ```

   > Remember to allow community recipes with `composer config extra.symfony.allow-contrib true` or during plugin installation process

1. Run `yarn encore dev` or `yarn encore production`

1. Database update:

    ```bash
    bin/console doctrine:migrations:migrate
    ```
    **Note:** If you are running it on production, add the `-e prod` flag to this command.

1. Clear application cache by using command:**

      ```bash
      bin/console cache:clear
      ```

## Asynchronous Messenger case

In case you use asynchronous Messenger transport by default, there is a need to configure all Wishlist commands to sync transport.
You can do this by configuring the `WishlistSyncCommandInterface` interface to sync transport (as presented on code listing below).

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            sync: 'sync://'
    routing:
        'Sylius\WishlistPlugin\Command\Wishlist\WishlistSyncCommandInterface': sync
```

All commands from the plugin implement the `WishlistSyncCommandInterface` interface, so there is no need for other configuration.
