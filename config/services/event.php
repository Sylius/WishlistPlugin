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

use Sylius\WishlistPlugin\EventSubscriber\CreateNewWishlistSubscriber;
use Sylius\WishlistPlugin\EventSubscriber\LoggedUserWishlistSubscriber;
use Sylius\WishlistPlugin\Factory\DomPdfOptionsFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.event_listener.create_new_wishlist', CreateNewWishlistSubscriber::class)
        ->args([
            '%sylius_wishlist_plugin.parameters.wishlist_cookie_token%',
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
            service('request_stack'),
        ])
        ->tag('kernel.event_subscriber', ['event' => 'kernel.exception']);

    $services->set('sylius_wishlist_plugin.event_subscriber.logged_user_wishlist_subscriber', LoggedUserWishlistSubscriber::class)
        ->args([
            service('sylius.section_resolver.uri_based'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
            service('sylius_wishlist_plugin.manager.wishlist_product'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('request_stack'),
            '%sylius_wishlist_plugin.parameters.wishlist_cookie_token%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sylius_wishlist_plugin.custom_factory.dom_pdf_options', DomPdfOptionsFactory::class);
};
