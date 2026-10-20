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

use Sylius\WishlistPlugin\Factory\CsvSerializerFactory;
use Sylius\WishlistPlugin\Factory\CsvWishlistProductFactory;
use Sylius\WishlistPlugin\Factory\DomPdfFactory;
use Sylius\WishlistPlugin\Factory\DomPdfOptionsFactory;
use Sylius\WishlistPlugin\Factory\WishlistFactory;
use Sylius\WishlistPlugin\Factory\WishlistProductFactory;
use Sylius\WishlistPlugin\Model\Factory\VariantPdfModelFactory;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.custom_factory.wishlist', WishlistFactory::class)
        ->private()
        ->decorate('sylius_wishlist_plugin.factory.wishlist')
        ->args([service('sylius_wishlist_plugin.custom_factory.wishlist.inner')]);

    $services->set('sylius_wishlist_plugin.custom_factory.wishlist_product', WishlistProductFactory::class)
        ->private()
        ->decorate('sylius_wishlist_plugin.factory.wishlist_product')
        ->args([service('sylius_wishlist_plugin.custom_factory.wishlist_product.inner')]);

    $services->set('sylius_wishlist_plugin.custom_factory.csv_wishlist_product', CsvWishlistProductFactory::class);

    $services->set('sylius_wishlist_plugin.custom_factory.csv.serializer', CsvSerializerFactory::class);

    $services->set('sylius_wishlist_plugin.custom_factory.dom_pdf', DomPdfFactory::class)
        ->args([service('sylius_wishlist_plugin.custom_factory.dom_pdf_options')]);

    $services->set('sylius_wishlist_plugin.custom_factory.dom_pdf_options', DomPdfOptionsFactory::class);

    $services->set('sylius_wishlist_plugin.model.factory.variant_pdf_model_factory', VariantPdfModelFactory::class);
};
