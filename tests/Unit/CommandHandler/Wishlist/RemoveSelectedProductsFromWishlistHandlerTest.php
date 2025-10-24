<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveSelectedProductsFromWishlist;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveSelectedProductsFromWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;
use Sylius\WishlistPlugin\Exception\WishlistProductNotFoundException;

final class RemoveSelectedProductsFromWishlistHandlerTest extends TestCase
{
    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&EntityManagerInterface $wishlistProductManager;

    private MockObject&WishlistItemInterface $wishlistItem;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private RemoveSelectedProductsFromWishlist $command;

    private RemoveSelectedProductsFromWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistProductManager = $this->createMock(EntityManagerInterface::class);
        $this->wishlistItem = $this->createMock(WishlistItemInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->command = new RemoveSelectedProductsFromWishlist(
            new ArrayCollection([$this->wishlistItem]),
        );
        $this->handler = new RemoveSelectedProductsFromWishlistHandler(
            $this->productVariantRepository,
            $this->wishlistProductManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveSelectedProductsFromWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveSelectedProductsFromWishlist(): void
    {
        $productVariant = $this->createMock(ProductVariantInterface::class);

        $this->wishlistItem
            ->expects($this->once())
            ->method('getWishlistProduct')
            ->willReturn($this->wishlistProduct);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('getVariant')
            ->willReturn($productVariant);
        $this->productVariantRepository
            ->expects($this->once())
            ->method('find')
            ->with($productVariant)
            ->willReturn($productVariant);

        $this->handler->__invoke($this->command);
    }

    public function testShouldThrowExceptionWhenVariantNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->wishlistItem
            ->expects($this->once())
            ->method('getWishlistProduct')
            ->willReturn($this->wishlistProduct);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('getVariant')
            ->willReturn(null);
        $this->productVariantRepository
            ->expects($this->once())
            ->method('find')
            ->with(null)
            ->willReturn(null);

        $this->handler->__invoke($this->command);
    }

    public function testShouldThrowExceptionWhenWishlistProductNotFound(): void
    {
        $this->expectException(WishlistProductNotFoundException::class);

        $this->wishlistItem
            ->expects($this->once())
            ->method('getWishlistProduct')
            ->willReturn(null);

        $this->handler->__invoke($this->command);
    }
}
