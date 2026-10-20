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

use Sylius\WishlistPlugin\Twig\Component\Product\AddToWishlistComponent;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius.wishlist_plugin.twig.component.product.add_to_wishlist', AddToWishlistComponent::class)
        ->args([
            service('sylius.wishlist_plugin.processor.add_product_variant_to_wishlist'),
            service('form.factory'),
            service('sylius.resolver.product_variant.default'),
            service('sylius.repository.product'),
            service('sylius.repository.product_variant'),
        ])
        ->tag('sylius.live_component.shop', ['key' => 'sylius_shop:product:add_to_wishlist']);
};
