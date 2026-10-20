# UPGRADE FROM 1.2 TO 1.3

1. The following service now requires a new argument:

   - `Sylius\WishlistPlugin\Processor\AddProductVariantToWishlistProcessor`:

     ```diff
     public function __construct(
         private Security $security,
         private WishlistExtension $wishlistExtension,
         private ChannelContextInterface $channelContext,
         private WishlistProductFactoryInterface $wishlistProductFactory,
         private RequestStack $requestStack,
         private TranslatorInterface $translator,
         private UrlGeneratorInterface $urlGenerator,
         private WishlistRepositoryInterface $wishlistRepository,
     +   private WishlistsResolverInterface $wishlistsResolver,
     )
     ```

   Wishlist creation for guests used to happen in the form builders and in
   `CreateNewWishlistSubscriber`. It now happens in this processor via the
   resolver, so the argument is mandatory. If you instantiate or decorate this
   service manually, you must pass an instance of
   `Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface`.

2. The following controller now requires two new arguments:

   - `Sylius\WishlistPlugin\Controller\Action\CreateNewWishlistAction`:

     ```diff
     public function __construct(
         private MessageBusInterface $commandBus,
         private RequestStack $requestStack,
         private TranslatorInterface $translator,
         private ChannelContextInterface $channelContext,
         private UrlGeneratorInterface $urlGenerator,
     +   private WishlistRepositoryInterface $wishlistRepository,
     +   private string $wishlistCookieToken,
     )
     ```

3. The following event subscriber no longer requires an argument:

   - `Sylius\WishlistPlugin\EventSubscriber\CreateNewWishlistSubscriber`:

     ```diff
     public function __construct(
         private string $wishlistCookieToken,
         private WishlistsResolverInterface $wishlistsResolver,
     -   private WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
         private RequestStack $requestStack,
     )
     ```

   The subscriber no longer generates a wishlist cookie token on its own; it only
   exposes the token of an already existing wishlist. Token generation now happens
   during wishlist creation (see point 1). The
   `Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface` argument
   is therefore removed. If you instantiate or decorate this service manually, you
   must drop this argument from the constructor call.

4. The following controller no longer requires two arguments:

   - `Sylius\WishlistPlugin\Controller\Action\ListWishlistProductsAction`:

     ```diff
     public function __construct(
           private CartContextInterface $cartContext,
           private FormFactoryInterface $formFactory,
           private Environment $twigEnvironment,
           private WishlistCommandProcessorInterface $wishlistCommandProcessor,
           private WishlistsResolverInterface $wishlistsResolver,
     -     private TranslatorInterface $translator, 
     -     private UrlGeneratorInterface $generator,
     )
     ```

5. The following service now accepts a new argument and passing the
   `Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface` argument to it is deprecated:

   - `Sylius\WishlistPlugin\EventSubscriber\LoggedUserWishlistSubscriber`:

     ```diff
     public function __construct(
         private SectionProviderInterface $uriBasedSectionContext,
     -   private WishlistsResolverInterface $wishlistsResolver,
     +   ?WishlistsResolverInterface $wishlistsResolver,
         private EntityManagerInterface $entityManager,
         private WishlistRepositoryInterface $wishlistRepository,
         private RequestStack $requestStack,
         private string $wishlistCookieToken,
     +   private ?WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver = null,
     )
     ```

   The subscriber now looks up the guest wishlists to assign on login by the wishlist
   cookie token resolved by `Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface`,
   so the `WishlistsResolverInterface` argument is no longer used and will be removed in 2.0.
   Not passing the `WishlistCookieTokenResolverInterface` argument is deprecated and it will
   be required in 2.0. If you instantiate or decorate this service manually, pass `null`
   instead of the wishlists resolver and an instance of `WishlistCookieTokenResolverInterface`
   as the last argument.

6. Logged in shop users no longer see guest wishlists matching their wishlist cookie
   token. `WishlistsResolver::resolve()`, `AddProductVariantToWishlistProcessor` and
   the `add_to_wishlist.html.twig` and `collective_actions.html.twig` templates now
   only use the wishlists assigned to the shop user. Previously, such guest wishlists
   were listed, but could not be opened, e.g. after impersonating a customer from the
   admin panel in a browser that still held a guest wishlist cookie.

7. The following methods and Twig function are deprecated and will be removed in 2.0,
   as they are no longer used:

   - `Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface::findAllByShopUserAndToken()`,
     use `findAllByShopUser()` instead
   - `Sylius\WishlistPlugin\Twig\WishlistExtension::findAllByShopUserAndToken()` and the
     `findAllByShopUserAndToken` Twig function, use `findAllByShopUser()` and the
     `findAllByShopUser` Twig function instead
