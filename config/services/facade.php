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

use Sylius\WishlistPlugin\Facade\WishlistProductFactoryFacade;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.facade.wishlist_product_factory_facade', WishlistProductFactoryFacade::class)
        ->args([service('sylius_wishlist_plugin.factory.wishlist_product')]);
};
