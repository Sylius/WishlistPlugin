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

use Sylius\WishlistPlugin\Duplicator\WishlistProductsToOtherWishlistDuplicator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.services.duplicator', WishlistProductsToOtherWishlistDuplicator::class)
        ->args([
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('sylius.repository.product_variant'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('request_stack'),
            service('translator'),
        ]);
};
