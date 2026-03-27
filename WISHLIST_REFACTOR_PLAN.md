# Wishlist Plugin 2.x — Refactoring Plan

## Context

We are analyzing PR #20 (upstream Sylius/WishlistPlugin) — "[MAINTENANCE] Wishlist refactor" by Cholin2000.
We compare it with wishlist modifications in our 3 projects: **dafre**, **elesto**, **solenne**.

Goal: extract what's good from PR #20 as smaller PRs, and where our projects have better solutions — implement the better version.

---

## PR #20 — what was done (upstream)

**Main change**: migration from Controller Actions + vanilla JS to Symfony UX LiveComponents.

### Removed files

**JavaScript (8 files):**
- `assets/shop/js/WishlistVariantButton.js`
- `assets/shop/js/copyToWishlistsListModal.js`
- `assets/shop/js/handleAddAnotherWishlistModal.js`
- `assets/shop/js/handleCopyToWishlistListModal.js`
- `assets/shop/js/handleEditWishlistModal.js`
- `assets/shop/js/handleRemoveWishlistModal.js`
- `assets/shop/js/handleWishlistMainCheckbox.js`
- `assets/shop/js/wishlistMainCheckboxUpdater.js`
- `assets/shop/js/wishlistModal.js`
- `assets/shop/js/index.js` (emptied)

**Controller Actions (10 files):**
- `AddProductsToCartAction.php`
- `AddWishlistToUserAction.php`
- `CleanWishlistAction.php`
- `CopySelectedProductsToOtherWishlistAction.php`
- `CreateNewWishlistAction.php`
- `RemoveProductFromWishlistAction.php`
- `RemoveProductVariantFromWishlistAction.php`
- `UpdateWishlistNameAction.php`
- `UpdateWishlistProductsQuantityAction.php`

### Added files

**16 LiveComponents (`src/Twig/Component/`):**

1. `Common/AddToWishlist/AddToWishlistButtonComponent.php` — add product to default wishlist
2. `Common/AddToWishlist/AddToSelectedWishlistButtonComponent.php` — add to selected wishlist
3. `WishlistDetails/Actions/ClearWishlistButtonComponent.php` — clear wishlist
4. `WishlistDetails/Actions/CreateNewWishlistButtonComponent.php` — create new wishlist (modal)
5. `WishlistDetails/CollectiveActions/BulkActionsComponent.php` — bulk: add to cart + remove selected
6. `WishlistDetails/CollectiveActions/CopyToWishlistComponent.php` — copy to another wishlist (modal)
7. `WishlistDetails/CollectiveActions/ExportActionsComponent.php` — export CSV/PDF
8. `WishlistDetails/GlobalActions/AddAllToCartComponent.php` — add all to cart
9. `WishlistDetails/GlobalActions/SaveChangesComponent.php` — save quantity changes
10. `WishlistDetails/GlobalActions/ImportFromCsvComponent.php` — import from CSV (modal)
11. `WishlistDetails/Item/Actions/RemoveItemButtonComponent.php` — remove item
12. `WishlistDetails/WishlistItemsComponent.php` — items list with selection
13. `WishlistGroup/Item/WishlistItemComponent.php` — row in wishlists listing
14. `WishlistGroup/Item/Buttons/EditWishlistButtonComponent.php` — edit name (modal)
15. `WishlistGroup/Item/Buttons/RemoveWishlistButtonComponent.php` — remove wishlist (modal)

**New controller:**
- `ExportSelectedToCsvAction.php` — export selected to CSV

**Configuration:**
- `config/services/twig/component/wishlist.xml` — LiveComponent service registration

### Changes in existing files

**Routing (`config/routes/shop.yaml`):**
- Removed 13 routes (replaced by LiveActions)
- URL pattern changed from `/wishlist/` to `/wishlists/`
- New/changed: export CSV, export PDF (POST), import CSV

**Services (`config/services/controller.xml`):**
- `sylius.command_bus` → `messenger.default_bus`
- `sylius.context.cart.new_shop_based.inner` → `sylius.context.cart`

**Twig Hooks:**
- All hooks changed from `template:` to `component:` with `props:`

**CSV Import/Export:**
- Import: rewritten to `SplFileObject`, auto-detect delimiter, header-based column mapping
- Export: direct CSV write instead of CsvSerializer

**CI:** tests disabled (WIP)

---

## Our modifications (dafre / elesto / solenne)

### Common modifications (2-3 projects)

| # | Modification | Dafre | Elesto | Solenne | In PR #20? |
|---|---|---|---|---|---|
| 1 | Custom LiveComponent add-to-wishlist (toggle without reload) | Yes | Yes (Stimulus) | Yes | Yes (but differently) |
| 2 | CachedWishlistsResolver (in-memory cache per request) | Yes | No | Yes | No |
| 3 | CreateNewWishlistAction → JSON response (AJAX modal) | Yes | Yes | Yes | No |
| 4 | Disabled default hooks on product cards | Yes | Yes | Yes | No |
| 5 | Badge with item count in header widget | Yes | Yes | Yes | No |
| 6 | Clear wishlist with confirmation | Yes | No | Yes | Yes |
| 7 | Redesign wishlist group — cards with thumbnails | Yes | Yes (table) | Yes | No |
| 8 | Back-to-wishlists navigation | Yes | No | Yes | No |

### Project-specific

**Dafre:**
- Elasticsearch wishlist index (product_ids for fast lookup)
- Doctrine lifecycle listener for ES sync
- Real-time sync on add/remove

**Elesto:**
- Rebranding "Wishlist" → "Shopping list" (factory decorator, handler override, clipboard icon)
- Sorting and pagination of wishlists (client-side JS)
- Bulk actions on wishlists list (select all, edit, delete)
- Total value calculation per wishlist

**Solenne:**
- Quantity +/- stepper with trash icon at qty=1
- PL/EN translations (~20 keys)
- Wishlist button on cart page per item

---

## Work plan — smaller PRs

Strategy: go through PR #20 step by step, extract logical pieces into separate PRs. Where our projects have better solutions — implement the better version.

### PR 0: Refactor remove commands ✅ DONE
Refactored `RemoveProductFromWishlist` and `RemoveProductVariantFromWishlist` commands to accept wishlist object via `setWishlist()` instead of wishlist token string — consistent with `AddProductToWishlist`. Authorization (`WishlistVoter::DELETE`) moved from handlers to API controllers.

**Why:** Wishlist token is not unique per wishlist (all anonymous wishlists share the same cookie token), making it impossible to target a specific wishlist.

**Branch:** `refactor-remove-product-command`

### PR 1: WishlistButtonComponent LiveComponent ✅ DONE
Replaced add-to-wishlist button (product cards + product show page) with `WishlistButtonComponent` — a single LiveComponent with toggle add/remove, heart icon state, variant awareness.

**What was done:**
- `WishlistButtonComponent` with `add(?int $wishlistId)` / `remove(?int $wishlistId)` LiveActions
- Dispatches existing commands via MessageBus (no direct persistence)
- `WishlistProductChecker` — checks if product is in any wishlist (decoratable, e.g. dafre could swap for ES)
- `WishlistsResolver::resolveById()` — finds wishlist by ID only among current user's wishlists
- Hookable template structure: `single/button`, `multiple/dropdown/toggle`, `multiple/dropdown/items/item`
- Removed old controllers, processor, form, JS, templates, routes
- Updated Behat tests with `@javascript` tag, LiveComponent wait pattern, test attributes

**Key design decisions:**
- One component for both product cards and product show page (listens to `SYLIUS_SHOP_VARIANT_CHANGED`)
- Resolver is the only source of wishlists (guarantees ownership)
- Component has 4 dependencies: ProductRepository, WishlistsResolver, WishlistProductChecker, MessageBus
- Templates use `sylius_test_html_attribute('wishlist-button', product.name)` for Behat targeting

**Branch:** `add-live-component`

### PR 2: CachedWishlistsResolver (OURS)
- [ ] Decorator with in-memory cache (from dafre/solenne)
- [ ] Solves N+1 problem when multiple components are on the same page
- [ ] Each LiveComponent calls `resolve()` — without cache this hits DB every time

### PR 3: Wishlist CRUD LiveComponents
- [ ] CreateNewWishlistButtonComponent (modal)
- [ ] EditWishlistButtonComponent (modal)
- [ ] RemoveWishlistButtonComponent (modal)
- [ ] **BETTER**: JSON response for create (all 3 projects needed this)
- [ ] Remove old JS modal handlers: `handleAddAnotherWishlistModal.js`, `handleEditWishlistModal.js`, `handleRemoveWishlistModal.js`

### PR 4: Wishlist Details — item actions
- [ ] RemoveItemButtonComponent (remove single item from wishlist)
- [ ] ClearWishlistButtonComponent (clear all items)
- [ ] WishlistItemsComponent (items list with checkbox selection)
- [ ] Remove old `RemoveProductVariantFromWishlistAction` shop controller
- [ ] Remove old `CleanWishlistAction` shop controller

### PR 5: Bulk/Collective Actions
- [ ] BulkActionsComponent (add selected to cart + remove selected)
- [ ] CopyToWishlistComponent (copy to another wishlist modal)
- [ ] SaveChangesComponent (save quantity changes)
- [ ] Remove old JS: `handleWishlistMainCheckbox.js`, `wishlistMainCheckboxUpdater.js`, `copyToWishlistsListModal.js`, `handleCopyToWishlistListModal.js`
- [ ] Remove old controllers: `AddProductsToCartAction`, `CopySelectedProductsToOtherWishlistAction`, `UpdateWishlistProductsQuantityAction`

### PR 6: Import/Export
- [ ] Improved CSV import (SplFileObject, auto-detect delimiter, header-based column mapping)
- [ ] ExportActionsComponent (CSV + PDF)
- [ ] ImportFromCsvComponent (modal)

### PR 7: Routing cleanup
- [ ] Normalize URLs from `/wishlist/` to `/wishlists/`
- [ ] Remove remaining old routes

### PR 8: Header widget with badge (OURS)
- [ ] Badge with item count in header widget
- [ ] Common need across all 3 projects

### PR 9: UX improvements (OURS)
- [ ] Back-to-wishlists navigation
- [ ] PL/EN translations
- [ ] Quantity stepper UX (optional)

---

## Status

| PR | Description | Status | Branch |
|---|---|---|---|
| PR 0 | Refactor remove commands | ✅ Done | `refactor-remove-product-command` |
| PR 1 | WishlistButtonComponent | ✅ Done | `add-live-component` |
| PR 2 | CachedWishlistsResolver | ⬜ TODO | — |
| PR 3 | Wishlist CRUD | ⬜ TODO | — |
| PR 4 | Item actions | ⬜ TODO | — |
| PR 5 | Bulk/Collective actions | ⬜ TODO | — |
| PR 6 | Import/Export | ⬜ TODO | — |
| PR 7 | Routing cleanup | ⬜ TODO | — |
| PR 8 | Header widget badge | ⬜ TODO | — |
| PR 9 | UX improvements | ⬜ TODO | — |

---

## Architecture notes (learned during PR 0 & 1)

- **Wishlist token is NOT unique per wishlist** — all wishlists of an anonymous user share the same cookie token. Never use token to identify a specific wishlist. Use ID + resolver for ownership check.
- **Resolver is the ownership gate** — `WishlistsResolver::resolve()` returns only current user's wishlists (query filters by shopUser + cookieToken + channel). `resolveById()` iterates over this.
- **Authorization belongs in controllers**, not handlers — handlers receive a wishlist object and trust the caller verified permissions.
- **Commands follow `setWishlist()` pattern** — both add and remove commands receive wishlist via setter, not constructor token.
- **Behat tests for LiveComponents need `@javascript` tag** — LiveComponent buttons are `<button>` elements (not `<a>`), SymfonyDriver can't click them. Wait pattern: `usleep(500000)` then `$element->waitFor(5000, fn ($el) => !$el->hasAttribute('busy'))`.
- **Use `sylius_test_html_attribute` for Behat targeting** — add product name / wishlist name as value for easy element lookup with `getElement()` and `getDefinedElements()`.
