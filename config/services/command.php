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

use Sylius\WishlistPlugin\Command\RemoveGuestWishlistsCommand;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius.wishlist_plugin.command.remove_guest_wishlists', RemoveGuestWishlistsCommand::class)
        ->args([service('sylius_wishlist_plugin.repository.wishlist')])
        ->tag('console.command');
};
