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

use Doctrine\Common\Collections\Collection;

final readonly class AddSelectedProductsToCart implements AddSelectedProductsToCartInterface
{
    public function __construct(private Collection $wishlistProducts)
    {
    }

    public function getWishlistProducts(): Collection
    {
        return $this->wishlistProducts;
    }
}
