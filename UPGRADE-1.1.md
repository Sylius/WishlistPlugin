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

## Template Variable Changes

### `templates/wishlist_details/collective_actions.html.twig`

The `wishlist` variable is now sourced from `hookable_metadata.context.wishlist` instead of being passed directly to the template.

**Before (1.0):**
```twig
{# wishlist was available directly as a template variable #}
{{ wishlist.id }}
```

**After (1.1):**
```twig
{% set wishlist = hookable_metadata.context.wishlist %}
{{ wishlist.id }}
```

**Action required:** If you have overridden this template or use it directly in your application, update your override to use `hookable_metadata.context.wishlist` to access the wishlist variable.
