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

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductVariantFromWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductVariantFromWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductVariantNotFoundException;

final class RemoveProductVariantFromWishlistHandlerTest extends TestCase
{
    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&ObjectManager $wishlistManager;

    private RemoveProductVariantFromWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->handler = new RemoveProductVariantFromWishlistHandler(
            $this->productVariantRepository,
            $this->wishlistManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveProductVariantFromWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveProductVariantFromWishlist(): void
    {
        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('getId')->willReturn(1);

        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $wishlistProduct->method('getVariant')->willReturn($variant);

        $wishlist->method('getWishlistProducts')->willReturn(new ArrayCollection([$wishlistProduct]));

        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn($variant);
        $wishlist->expects($this->once())->method('removeProduct')->with($wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('flush');

        $command = new RemoveProductVariantFromWishlist(1);
        $command->setWishlist($wishlist);

        $this->assertSame($wishlist, ($this->handler)($command));
    }

    public function testShouldThrowExceptionWhenProductVariantNotFound(): void
    {
        $this->expectException(ProductVariantNotFoundException::class);

        $wishlist = $this->createMock(WishlistInterface::class);

        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        $command = new RemoveProductVariantFromWishlist(1);
        $command->setWishlist($wishlist);

        ($this->handler)($command);
    }
}
