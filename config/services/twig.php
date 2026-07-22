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

use Sylius\WishlistPlugin\Twig\WishlistExtension;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.twig.extension.wishlist_extension', WishlistExtension::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
        ])
        ->tag('twig.extension');
};
