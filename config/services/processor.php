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

use Sylius\WishlistPlugin\Processor\AddProductVariantToWishlistProcessor;
use Sylius\WishlistPlugin\Processor\SelectedWishlistProductsProcessor;
use Sylius\WishlistPlugin\Processor\VariantPdfModelProcessor;
use Sylius\WishlistPlugin\Processor\WishlistCommandProcessor;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.processor.wishlist_command_processor', WishlistCommandProcessor::class);

    $services->set('sylius_wishlist_plugin.processor.selected_wishlist_products_processor', SelectedWishlistProductsProcessor::class);

    $services->set('sylius_wishlist_plugin.processor.variant_pdf_model_processor', VariantPdfModelProcessor::class)
        ->args([service('sylius_wishlist_plugin.services.generator.model_creator')]);

    $services->set('sylius.wishlist_plugin.processor.add_product_variant_to_wishlist', AddProductVariantToWishlistProcessor::class)
        ->args([
            service('security.helper'),
            service('sylius_wishlist_plugin.twig.extension.wishlist_extension'),
            service('sylius.context.channel'),
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('router'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
        ]);
};
