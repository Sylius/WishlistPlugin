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

namespace Sylius\WishlistPlugin\Checker;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;

interface WishlistProductCheckerInterface
{
    /** @param WishlistInterface[] $wishlists */
    public function isProductInAnyWishlist(ProductInterface $product, array $wishlists): bool;
}
