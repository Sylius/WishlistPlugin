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

final readonly class RemoveProductVariantFromWishlist implements WishlistSyncCommandInterface
{
    public function __construct(
        private int $productVariantId,
        private string $wishlistToken,
    ) {
    }

    public function getProductVariantIdValue(): int
    {
        return $this->productVariantId;
    }

    public function getWishlistTokenValue(): string
    {
        return $this->wishlistToken;
    }
}
