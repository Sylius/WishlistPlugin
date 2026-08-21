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

use Sylius\WishlistPlugin\Serializer\ContextBuilder\WishlistTokenValueAwareContextBuilder;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius.wishlist_plugin.serializer.context_builder.wishlist_token_value_aware_context_builder', WishlistTokenValueAwareContextBuilder::class)
        ->decorate('api_platform.serializer.context_builder', null, 64)
        ->args([
            service('.inner'),
            service('sylius_wishlist_plugin.repository.wishlist'),
        ]);
};
