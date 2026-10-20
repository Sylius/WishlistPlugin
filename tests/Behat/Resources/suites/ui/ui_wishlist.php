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

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;

return (new Config())
    ->withProfile(
        (new Profile('default'))
        ->withSuite(
            (new Suite('ui_wishlist'))
            ->withContexts(
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.setup.channel',
                'sylius.behat.context.setup.product',
                'sylius.behat.context.setup.customer',
                'sylius.behat.context.setup.shop_security',
                'sylius.behat.context.transform.lexical',
                'sylius.behat.context.transform.product',
                'sylius.behat.context.transform.channel',
                'sylius.behat.context.ui.shop.product',
                'sylius.behat.context.ui.shop.cart',
                'sylius.behat.context.ui.shop.account',
                MinkContext::class,
                'sylius_wishlist_plugin.behat.context.setup.wishlist',
                'sylius_wishlist_plugin.behat.context.ui.wishlist',
                'sylius_wishlist_plugin.behat.context.common.wishlist',
            )
            ->withFilter(new TagFilter('@wishlist&&@ui')),
        ),
    )
;
