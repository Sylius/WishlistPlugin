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

use Sylius\WishlistPlugin\Controller\Action\AddProductsToCartAction;
use Sylius\WishlistPlugin\Controller\Action\AddProductToSelectedWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\AddProductToWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\AddProductVariantToWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\AddSelectedProductsToCartAction;
use Sylius\WishlistPlugin\Controller\Action\AddWishlistToUserAction;
use Sylius\WishlistPlugin\Controller\Action\ApiPlatform\RemoveProductFromWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\ApiPlatform\RemoveProductVariantFromWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\ApiPlatform\RemoveWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\BaseWishlistProductsAction;
use Sylius\WishlistPlugin\Controller\Action\BaseWishlistsListingAction;
use Sylius\WishlistPlugin\Controller\Action\CleanWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\CopySelectedProductsToOtherWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\CreateNewWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\ExportSelectedProductsToCsvAction;
use Sylius\WishlistPlugin\Controller\Action\ExportWishlistToPdfAction;
use Sylius\WishlistPlugin\Controller\Action\ImportWishlistFromCsvAction;
use Sylius\WishlistPlugin\Controller\Action\ListWishlistProductsAction;
use Sylius\WishlistPlugin\Controller\Action\ListWishlistsAction;
use Sylius\WishlistPlugin\Controller\Action\RemoveSelectedProductsFromWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\RenderHeaderTemplateAction;
use Sylius\WishlistPlugin\Controller\Action\ShowChosenWishlistAction;
use Sylius\WishlistPlugin\Controller\Action\UpdateWishlistNameAction;
use Sylius\WishlistPlugin\Controller\Action\UpdateWishlistProductsQuantityAction;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sylius_wishlist_plugin.controller.action.api_platform.remove_product_from_wishlist_action', RemoveProductFromWishlistAction::class)
        ->args([service('api_platform.message_bus')])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.api_platform.remove_product_variant_from_wishlist_action', RemoveProductVariantFromWishlistAction::class)
        ->args([service('api_platform.message_bus')])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.api_platform.remove_wishlist_action', RemoveWishlistAction::class)
        ->args([service('api_platform.message_bus')])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.base_wishlist_products_action', BaseWishlistProductsAction::class)
        ->abstract()
        ->args([
            service('sylius.context.cart'),
            service('form.factory'),
            service('request_stack'),
            service('sylius_wishlist_plugin.processor.wishlist_command_processor'),
            service('sylius.command_bus'),
            service('router'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('translator'),
        ]);

    $services->set('sylius_wishlist_plugin.controller.action.base_wishlists_listing_action', BaseWishlistsListingAction::class)
        ->abstract()
        ->args([
            service('twig'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
        ]);

    $services->set('sylius_wishlist_plugin.controller.action.list_wishlists', ListWishlistsAction::class)
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlists_listing_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.render_header_template', RenderHeaderTemplateAction::class)
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlists_listing_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_product_to_wishlist', AddProductToWishlistAction::class)
        ->args([
            service('sylius.repository.product'),
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
            service('sylius_wishlist_plugin.manager.wishlist'),
            service('sylius.context.channel'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.remove_product_from_wishlist', \Sylius\WishlistPlugin\Controller\Action\RemoveProductFromWishlistAction::class)
        ->args([
            service('sylius_wishlist_plugin.context.wishlist'),
            service('sylius.repository.product'),
            service('sylius_wishlist_plugin.manager.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('router'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.clean_wishlist', CleanWishlistAction::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius_wishlist_plugin.manager.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('router'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_product_variant_to_wishlist', AddProductVariantToWishlistAction::class)
        ->args([
            service('sylius.repository.product_variant'),
            service('sylius_wishlist_plugin.factory.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('router'),
            service('sylius_wishlist_plugin.repository.wishlist'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.remove_product_variant_from_wishlist', \Sylius\WishlistPlugin\Controller\Action\RemoveProductVariantFromWishlistAction::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius.repository.product_variant'),
            service('sylius_wishlist_plugin.manager.wishlist_product'),
            service('request_stack'),
            service('translator'),
            service('router'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.remove_selected_products_from_wishlist', RemoveSelectedProductsFromWishlistAction::class)
        ->public()
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlist_products_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_selected_products_to_cart', AddSelectedProductsToCartAction::class)
        ->public()
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlist_products_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.export_selected_products_to_csv', ExportSelectedProductsToCsvAction::class)
        ->args([
            service('sylius.context.cart.new_shop_based.inner'),
            service('form.factory'),
            service('request_stack'),
            service('sylius_wishlist_plugin.processor.wishlist_command_processor'),
            service('router'),
            service('translator'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius.command_bus'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.import_from_csv', ImportWishlistFromCsvAction::class)
        ->args([
            service('form.factory'),
            service('request_stack'),
            service('twig'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
            service('sylius.command_bus'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.list_wishlist_products', ListWishlistProductsAction::class)
        ->args([
            service('sylius.context.cart'),
            service('form.factory'),
            service('twig'),
            service('sylius_wishlist_plugin.processor.wishlist_command_processor'),
            service('sylius_wishlist_plugin.resolver.wishlists_resolver'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_products_to_wishlist', AddProductsToCartAction::class)
        ->public()
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlist_products_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.export_wishlist_to_pdf_action', ExportWishlistToPdfAction::class)
        ->public()
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlist_products_action')
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.create_new_wishlist', CreateNewWishlistAction::class)
        ->args([
            service('sylius.command_bus'),
            service('request_stack'),
            service('translator'),
            service('sylius.context.channel'),
            service('router.default'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            '%sylius_wishlist_plugin.parameters.wishlist_cookie_token%',
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.show_chosen_wishlist', ShowChosenWishlistAction::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius.context.cart'),
            service('form.factory'),
            service('twig'),
            service('sylius_wishlist_plugin.processor.wishlist_command_processor'),
            service('router.default'),
            service('sylius_wishlist_plugin.resolver.wishlist_cookie_token_resolver'),
            service('security.token_storage'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_product_to_selected_wishlist', AddProductToSelectedWishlistAction::class)
        ->args([
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('sylius.repository.product'),
            service('request_stack'),
            service('translator'),
            service('router'),
            service('sylius.command_bus'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.copy_selected_products_to_other_wishlist', CopySelectedProductsToOtherWishlistAction::class)
        ->args([
            service('sylius.command_bus'),
            service('request_stack'),
            service('translator'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.update_wishlist_name_action', UpdateWishlistNameAction::class)
        ->args([
            service('sylius.command_bus'),
            service('request_stack'),
            service('translator'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('router'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.add_wishlists_to_user', AddWishlistToUserAction::class)
        ->args([
            service('sylius.command_bus'),
            service('request_stack'),
            service('translator'),
            service('sylius_wishlist_plugin.repository.wishlist'),
            service('router'),
            service('security.token_storage'),
            service('sylius_wishlist_plugin.resolver.token_user_resolver'),
        ])
        ->tag('controller.service_arguments');

    $services->set('sylius_wishlist_plugin.controller.action.update_wishlist_products_quantity', UpdateWishlistProductsQuantityAction::class)
        ->public()
        ->parent('sylius_wishlist_plugin.controller.action.base_wishlist_products_action')
        ->args([service('sylius_wishlist_plugin.manager.wishlist_product')])
        ->tag('controller.service_arguments');
};
