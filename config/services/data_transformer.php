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

use Sylius\WishlistPlugin\DataTransformer\Wishlist\WishlistTokenValueAwareInputCommandDataTransformer;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.data_transformer.wishlist.wishlist_token_value_aware_input_command_data_transformer', WishlistTokenValueAwareInputCommandDataTransformer::class)
        ->tag('sylius.api.command_data_transformer');
};
