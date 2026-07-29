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

      ```diff
      <service id="sylius_wishlist_plugin.controller.action.list_wishlist_products" class="Sylius\WishlistPlugin\Controller\Action\ListWishlistProductsAction">
          <argument type="service" id="sylius.context.cart"/>
          <argument type="service" id="form.factory"/>
          <argument type="service" id="twig"/>
          <argument type="service" id="sylius_wishlist_plugin.processor.wishlist_command_processor"/>
          <argument type="service" id="sylius_wishlist_plugin.resolver.wishlists_resolver"/>
      -   <argument type="service" id="translator"/>
      -   <argument type="service" id="router"/>
          <tag name="controller.service_arguments"/>
      </service>
      ```
