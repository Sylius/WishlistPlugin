# Legacy Installation

## Composer:
 ```bash
 composer require sylius/wishlist-plugin
 ```

## Basic configuration:
1. Add plugin dependencies to your `config/bundles.php` file (if not added automatically):

    ```php
    # config/bundles.php
    
    return [
        ...
        Sylius\WishlistPlugin\SyliusWishlistPlugin::class => ['all' => true],
    ];
    ```

1. Import required config in your `config/packages/_sylius.yaml` file:
    ```yaml
    # config/packages/_sylius.yaml
    
    imports:
          ...
          - { resource: "@SyliusWishlistPlugin/config/config.yaml" }
    ```

1. Import routing in your `config/routes.yaml` file:

    ```yaml
    
    # config/routes.yaml
    ...
    sylius_wishlist:
        resource: "@SyliusWishlistPlugin/config/routes.yaml"
    ```

1. Install assets:
    ```bash
    bin/console assets:install --symlink
    ```

1. Add entrypoint import:
    ```yaml
    // assets/admin/entrypoint.js
    import '@vendor/sylius/wishlist-plugin/assets/admin/entrypoint'
    ```
    ```yaml
    // assets/shop/entrypoint.js
    import '@vendor/sylius/wishlist-plugin/assets/shop/entrypoint'
    ```

1. Build assets:
    ```bash
      yarn install
    ```
    ```bash
      yarn encore dev
    ```

1. Database update:
```bash
  bin/console doctrine:migrations:migrate
```
**Note:** If you are running it on production, add the `-e prod` flag to this command.

**Clear application cache by using command:**
```bash
  bin/console cache:clear
```

