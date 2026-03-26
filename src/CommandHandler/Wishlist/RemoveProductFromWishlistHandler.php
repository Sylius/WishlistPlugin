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
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductFromWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RemoveProductFromWishlistHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ObjectManager $wishlistManager,
    ) {
    }

    public function __invoke(RemoveProductFromWishlist $removeProductFromWishlist): WishlistInterface
    {
        /** @var ?ProductInterface $product */
        $product = $this->productRepository->find($removeProductFromWishlist->productId);

        if (null === $product) {
            throw new ProductNotFoundException(
                sprintf('The Product %s does not exist', $removeProductFromWishlist->productId),
            );
        }

        $wishlist = $removeProductFromWishlist->getWishlist();

        /** @var WishlistProductInterface $wishlistProduct */
        foreach ($wishlist->getWishlistProducts() as $wishlistProduct) {
            if ($wishlistProduct->getProduct()?->getId() === $product->getId()) {
                $wishlist->removeProduct($wishlistProduct);
            }
        }

        $this->wishlistManager->flush();

        return $wishlist;
    }
}
