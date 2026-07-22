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

use Sylius\WishlistPlugin\Context\WishlistContext;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.context.wishlist', WishlistContext::class)
        ->public()
        ->args([
            service('security.token_storage'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.factory.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
            service('sylius.context.channel'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
        ]);
};
