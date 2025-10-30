# UPGRADE FROM 1.0 TO 1.1

## Granular Twig Hooks

We've introduced granular Twig hooks to provide better customization capabilities. The following templates have been deprecated and will be removed in 2.0:

- `@SyliusWishlistPlugin/common/remove_from_wishlist.html.twig`
- `@SyliusWishlistPlugin/wishlist_details/global_actions.html.twig`
- `@SyliusWishlistPlugin/wishlist_details/item.html.twig`

### Migration to Granular Hooks

If you have overridden any of the deprecated templates, consider migrating to the new granular Twig hooks system to prepare for the 2.0 upgrade.

The new granular hooks are defined in:
- `config/twig_hooks/wishlist.yaml` - for wishlist details page hooks
- `config/twig_hooks/wishlist_group.yaml` - for wishlist group-related hooks
- `config/twig_hooks/common/` - for common hooks used across multiple pages
- `config/twig_hooks/product/` - for product-related hooks
