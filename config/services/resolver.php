<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sylius\WishlistPlugin\Resolver\GenerateDataUriForImageResolver;
use Sylius\WishlistPlugin\Resolver\ShopUserWishlistResolver;
use Sylius\WishlistPlugin\Resolver\TokenUserResolver;
use Sylius\WishlistPlugin\Resolver\VariantImageToDataUriResolver;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolver;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolver;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.resolver.shop_user_wishlist_resolver', ShopUserWishlistResolver::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.custom_factory.wishlist'),
            service('sylius.context.channel'),
        ]);

    $services->set('sylius_wishlist_plugin.resolver.wishlists_resolver', WishlistsResolver::class)
        ->lazy(WishlistsResolverInterface::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('security.token_storage'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
            service('sylius.context.channel'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
            service('sylius.command_bus'),
        ]);

    $services->set('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver', WishlistCookieTokenResolver::class)
        ->lazy(WishlistCookieTokenResolverInterface::class)
        ->args([
            service('request_stack'),
            '%sylius_wishlist_plugin.parameters.wishlist_cookie_token%',
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('security.token_storage'),
            service('sylius.context.channel'),
        ]);

    $services->set('sylius_wishlist_plugin.resolver.token_user_resolver', TokenUserResolver::class);

    $services->set('sylius_wishlist_plugin.resolver.variant_image_path_resolver', VariantImageToDataUriResolver::class)
        ->args([service('sylius_wishlist_plugin.resolver.generate_data_uri_for_image_resolver')]);

    $services->set('sylius_wishlist_plugin.resolver.generate_data_uri_for_image_resolver', GenerateDataUriForImageResolver::class)
        ->args([
            service('assets.empty_package'),
            service('liip_imagine.service.filter'),
            'sylius_shop_product_thumbnail',
        ]);
};
