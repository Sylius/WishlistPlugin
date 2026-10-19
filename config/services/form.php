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

use Sylius\Bundle\CoreBundle\Form\Type\Order\AddToCartType;
use Sylius\WishlistPlugin\Form\Extension\AddToCartTypeExtension;
use Sylius\WishlistPlugin\Form\Type\AddProductsToCartType;
use Sylius\WishlistPlugin\Form\Type\AddToWishlistType;
use Sylius\WishlistPlugin\Form\Type\CreateNewWishlistType;
use Sylius\WishlistPlugin\Form\Type\WishlistCollectionType;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.form.type.add_products_to_cart', AddProductsToCartType::class)
        ->args([
            service('sylius.factory.add_to_cart_command'),
            service('sylius.factory.order_item'),
            service('sylius.modifier.order_item_quantity'),
        ])
        ->tag('form.type');

    $services->set('sylius_wishlist_plugin.form.type.extension.add_to_cart_type_extension', AddToCartTypeExtension::class)
        ->args([service('sylius_wishlist_plugin.resolver.wishlists_resolver')])
        ->tag('form.type_extension', ['extended_type' => AddToCartType::class]);

    $services->set('sylius_wishlist_plugin.form.type.create_new_wishlist', CreateNewWishlistType::class)
        ->tag('form.type');

    $services->set('sylius_wishlist_plugin.form.type.wishlist_collection_type', WishlistCollectionType::class)
        ->args([
            service('translator'),
            service('sylius_wishlist_plugin.processor.selected_wishlist_products_processor'),
        ])
        ->tag('form.type');

    $services->set('sylius.wishlist_plugin.form.type.add_to_wishlist_type', AddToWishlistType::class)
        ->args([service('sylius_wishlist_plugin.resolver.wishlists_resolver')])
        ->tag('form.type');
};
