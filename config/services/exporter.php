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

use Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporter;
use Sylius\WishlistPlugin\Exporter\WishlistToPdfExporter;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.services.exporter', WishlistToPdfExporter::class)
        ->args([
            service('sylius_wishlist_plugin.processor.variant_pdf_model_processor'),
            service('sylius_wishlist_plugin.services.exporter.dom_pdf_wishlist_exporter'),
        ]);

    $services->set('sylius_wishlist_plugin.services.exporter.dom_pdf_wishlist_exporter', DomPdfWishlistExporter::class)
        ->args([
            service('twig'),
            service('sylius_wishlist_plugin.custom_factory.dom_pdf'),
        ]);
};
