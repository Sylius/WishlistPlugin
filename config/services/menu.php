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

use Sylius\WishlistPlugin\Menu\ShopUserAccountMenuListener;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.event_listener.menu.shop_user_account', ShopUserAccountMenuListener::class)
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.shop.account', 'method' => 'addWishlistItem']);
};
