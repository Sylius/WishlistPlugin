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

namespace Sylius\WishlistPlugin\CommandHandler\Wishlist;

use Doctrine\Persistence\ObjectManager;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductVariantFromWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductVariantNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RemoveProductVariantFromWishlistHandler
{
    public function __construct(
        private ProductVariantRepositoryInterface $productVariantRepository,
        private ObjectManager $wishlistManager,
    ) {
    }

    public function __invoke(RemoveProductVariantFromWishlist $removeProductVariantFromWishlist): WishlistInterface
    {
        /** @var ?ProductVariantInterface $variant */
        $variant = $this->productVariantRepository->find($removeProductVariantFromWishlist->productVariantId);

        if (null === $variant) {
            throw new ProductVariantNotFoundException(
                sprintf('The Product Variant %s does not exist', $removeProductVariantFromWishlist->productVariantId),
            );
        }

        $wishlist = $removeProductVariantFromWishlist->getWishlist();

        /** @var WishlistProductInterface $wishlistProduct */
        foreach ($wishlist->getWishlistProducts() as $wishlistProduct) {
            if ($wishlistProduct->getVariant()?->getId() === $variant->getId()) {
                $wishlist->removeProduct($wishlistProduct);
            }
        }

        $this->wishlistManager->flush();

        return $wishlist;
    }
}
