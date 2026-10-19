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

use Sylius\WishlistPlugin\Serializer\Denormalizer\WishlistTokenValueAwareDenormalizer;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius.wishlist_plugin.serializer.denormalizer.wishlist_token_value_aware_denormalizer', WishlistTokenValueAwareDenormalizer::class)
        ->decorate('sylius_api.denormalizer.command')
        ->args([service('.inner')])
        ->tag('serializer.normalizer');
};
