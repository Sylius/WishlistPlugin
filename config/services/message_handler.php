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

use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductsToCartHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductToSelectedWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductToWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductVariantToWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddSelectedProductsToCartHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddWishlistToUserHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CopySelectedProductsToOtherWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateNewWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\ExportSelectedProductsFromWishlistToPdfHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\ExportWishlistToCsvHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\ImportWishlistFromCsvHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductFromWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductVariantFromWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveSelectedProductsFromWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveWishlistHandler;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\UpdateWishlistNameHandler;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_products_to_wishlist_handler', AddProductsToCartHandler::class)
        ->args([
            service('sylius.modifier.order'),
            service('sylius.repository.order'),
            service('sylius.checker.inventory.availability'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_product_to_wishlist_handler', AddProductToWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('sylius.repository.product'),
            service('sylius_wishlist_plugin.manager.wishlist'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_product_variant_to_wishlist_handler', AddProductVariantToWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('sylius.repository.product_variant'),
            service('sylius_wishlist_plugin.manager.wishlist'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.remove_product_from_wishlist_handler', RemoveProductFromWishlistHandler::class)
        ->args([
            service('sylius.repository.product'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.repository.wishlist_product'),
            service('sylius_wishlist_plugin.manager.wishlist'),
            service('security.authorization_checker'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.remove_product_variant_from_wishlist_handler', RemoveProductVariantFromWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius.repository.product_variant'),
            service('sylius_wishlist_plugin.repository.wishlist_product'),
            service('sylius_wishlist_plugin.manager.wishlist'),
            service('security.authorization_checker'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.create_wishlist_handler', CreateWishlistHandler::class)
        ->args([
            service('security.token_storage'),
            service('sylius_wishlist_plugin.custom_factory.wishlist'),
            service('sylius_wishlist_plugin.resolver.shop_user_wishlist_resolver'),
            service('sylius_wishlist_plugin.manager.wishlist'),
            service('sylius.repository.channel'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
            service('request_stack'),
            '%sylius_wishlist_plugin.parameters.wishlist_cookie_token%',
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.remove_wishlist', RemoveWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.manager.wishlist'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_selected_products_to_cart', AddSelectedProductsToCartHandler::class)
        ->args([
            service('sylius.modifier.order_item_quantity'),
            service('sylius.modifier.order'),
            service('sylius.repository.order'),
            service('sylius.checker.inventory.availability'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.remove_selected_products_from_wishlist', RemoveSelectedProductsFromWishlistHandler::class)
        ->args([
            service('sylius.repository.product_variant'),
            service('sylius.manager.order'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.import_from_csv', ImportWishlistFromCsvHandler::class)
        ->args([
            service('sylius_wishlist_plugin.controller.action.add_product_variant_to_wishlist'),
            service('sylius.repository.product_variant'),
            '%sylius_wishlist_plugin.parameters.allowed_mime_types%',
            service('sylius_wishlist_plugin.custom_factory.csv.serializer'),
            service('request_stack'),
            service('translator'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.export_to_csv', ExportWishlistToCsvHandler::class)
        ->args([
            service('sylius_wishlist_plugin.custom_factory.csv_wishlist_product'),
            service('sylius_wishlist_plugin.custom_factory.csv.serializer'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.export_selected_products_from_wishlist_to_pdf', ExportSelectedProductsFromWishlistToPdfHandler::class)
        ->args([service('sylius_wishlist_plugin.services.exporter')])
        ->tag('messenger.message_handler', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_product_to_selected_wishlist_handler', AddProductToSelectedWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('sylius_wishlist_plugin.repository.wishlist'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.create_new_wishlist_handler', CreateNewWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('security.token_storage'),
            service('sylius_wishlist_plugin.factory.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
            service('sylius.repository.channel'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.copy_selected_products_to_other_wishlist', CopySelectedProductsToOtherWishlistHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.services.duplicator'),
        ])
        ->tag('sylius.wishlist_plugin.command_bus', ['bus' => 'sylius.command_bus']);

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.update_wishlist_name_handler', UpdateWishlistNameHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
        ])
        ->tag('messenger.message_handler');

    $services->set('sylius_wishlist_plugin.command_handler.wishlist.add_wishlists_to_user_handler', AddWishlistToUserHandler::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
        ])
        ->tag('messenger.message_handler');
};
