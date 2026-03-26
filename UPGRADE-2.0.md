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
