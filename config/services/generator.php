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

use Sylius\WishlistPlugin\Generator\ModelCreator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.services.generator.model_creator', ModelCreator::class)
        ->args([
            service('sylius_wishlist_plugin.resolver.variant_image_path_resolver'),
            service('sylius_wishlist_plugin.model.factory.variant_pdf_model_factory'),
            service('request_stack'),
        ]);
};
