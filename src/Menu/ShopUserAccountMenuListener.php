<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class ShopUserAccountMenuListener
{
    public function addWishlistItem(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $wishlistItem = $menu->addChild('wishlists', [
            'route' => 'sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists',
        ]);

        $wishlistItem->setLabel('sylius_wishlist_plugin.ui.your_wishlists');
        $wishlistItem->setLabelAttribute('icon', 'mdi:heart-outline');
    }
}
