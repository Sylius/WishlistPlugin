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

namespace Sylius\WishlistPlugin\Command\Wishlist;

use Sylius\WishlistPlugin\Entity\WishlistInterface;

interface UpdateWishlistNameInterface extends WishlistSyncCommandInterface
{
    public function getName(): string;

    public function getWishlist(): WishlistInterface;
}
