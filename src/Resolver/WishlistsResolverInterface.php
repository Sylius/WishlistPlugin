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

namespace Sylius\WishlistPlugin\Resolver;

use Sylius\WishlistPlugin\Entity\WishlistInterface;

interface WishlistsResolverInterface
{
    /** @return WishlistInterface[] */
    public function resolve(): array;

    /** @return WishlistInterface[] */
    public function resolveAndCreate(): array;

    public function resolveById(int $wishlistId): ?WishlistInterface;
}
