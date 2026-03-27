# UPGRADE FROM 1.x TO 2.0

## Remove commands no longer accept wishlist token

`RemoveProductFromWishlist` and `RemoveProductVariantFromWishlist` commands no longer accept a wishlist token string in their constructor. Instead, they follow the same pattern as `AddProductToWishlist` — you pass the product/variant ID in the constructor and set the wishlist object via `setWishlist()`.

**Reason:** The wishlist token is not unique per wishlist — all wishlists of an anonymous user share the same cookie token. This made it impossible to target a specific wishlist in multi-wishlist scenarios.

**Before (1.x):**
```php
$command = new RemoveProductFromWishlist($productId, $wishlistToken);
$this->messageBus->dispatch($command);
```

**After (2.0):**
```php
$command = new RemoveProductFromWishlist($productId);
$command->setWishlist($wishlist);
$this->messageBus->dispatch($command);
```

The same applies to `RemoveProductVariantFromWishlist`.

### Authorization moved to controllers

The `RemoveProductFromWishlistHandler` and `RemoveProductVariantFromWishlistHandler` no longer perform authorization checks. Authorization (`WishlistVoter::DELETE`) is now the responsibility of the calling controller. If you dispatch these commands from custom code, ensure you verify permissions before dispatching.

## Add-to-wishlist button replaced with LiveComponent

The add-to-wishlist button on product cards and product show page has been replaced with `WishlistButtonComponent` — a Symfony UX LiveComponent that toggles add/remove without page reload.

### Removed classes

- `Sylius\WishlistPlugin\Controller\Action\AddProductToWishlistAction`
- `Sylius\WishlistPlugin\Controller\Action\AddProductToSelectedWishlistAction`
- `Sylius\WishlistPlugin\Twig\Component\Product\AddToWishlistComponent`
- `Sylius\WishlistPlugin\Processor\AddProductVariantToWishlistProcessor` (and interface)
- `Sylius\WishlistPlugin\Form\Type\AddToWishlistType`

### Removed routes

- `wishlist_add_product` (`/wishlist/add/{productId}`)
- `wishlist_add_product_to_selected_wishlist` (`/wishlist/{wishlistId}/add/{productId}`)

### Removed JavaScript

- `assets/shop/js/WishlistVariantButton.js`

### New classes

- `Sylius\WishlistPlugin\Twig\Component\WishlistButtonComponent` — LiveComponent replacing the above
- `Sylius\WishlistPlugin\Checker\WishlistProductChecker` (and interface) — checks if product is in any wishlist

### New resolver method

`WishlistsResolverInterface` has a new method `resolveById(int $wishlistId): ?WishlistInterface` that finds a wishlist by ID only among the current user's wishlists.

### Template changes

All templates under `templates/common/add_to_wishlist/` and `templates/product/show/add_to_wishlist/` have been removed and replaced with:

- `templates/common/wishlist_button.html.twig` — main component template
- `templates/common/wishlist_button/single/button.html.twig` — single wishlist button
- `templates/common/wishlist_button/multiple/dropdown.html.twig` — multi-wishlist dropdown
- `templates/common/wishlist_button/multiple/dropdown/toggle.html.twig` — dropdown toggle
- `templates/common/wishlist_button/multiple/dropdown/items.html.twig` — dropdown items list
- `templates/common/wishlist_button/multiple/dropdown/items/item.html.twig` — single dropdown item

### Twig hooks changes

Old hooks under `sylius_shop.common.add_to_wishlist.*` and `sylius_shop.product.show.add_to_wishlist.*` have been removed. New hooks:

- `sylius_shop.common.wishlist_button.single`
- `sylius_shop.common.wishlist_button.multiple`
- `sylius_shop.common.wishlist_button.multiple.dropdown`
- `sylius_shop.common.wishlist_button.multiple.dropdown.items`

### Migration for custom overrides

If you overrode the add-to-wishlist templates or hooked into `sylius_shop.common.add_to_wishlist.*`, migrate to the new hook names. The component now handles both product cards and product show page — there is no separate product page component.

The button dispatches existing `AddProductToWishlist`, `AddProductVariantToWishlist` and `RemoveProductFromWishlist` commands via MessageBus, so any custom command handlers will still work.
