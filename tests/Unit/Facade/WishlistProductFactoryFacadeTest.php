<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Facade;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Facade\WishlistProductFactoryFacade;
use Sylius\WishlistPlugin\Facade\WishlistProductFactoryFacadeInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;

final class WishlistProductFactoryFacadeTest extends TestCase
{
    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private WishlistProductFactoryFacade $facade;

    protected function setUp(): void
    {
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->facade = new WishlistProductFactoryFacade($this->wishlistProductFactory);
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistProductFactoryFacade::class, $this->facade);
    }

    public function testShouldImplementWishlistProductFactoryFacadeInterface(): void
    {
        $this->assertInstanceOf(WishlistProductFactoryFacadeInterface::class, $this->facade);
    }

    public function testShouldCreateWishlistProductVariantAndAddItToWishlist(): void
    {
        $productVariant = $this->createMock(ProductVariantInterface::class);
        $this->wishlistProductFactory
            ->expects($this->once())
            ->method('createForWishlistAndVariant')
            ->with($this->wishlist, $productVariant)
            ->willReturn($this->wishlistProduct);
        $this->wishlist
            ->expects($this->once())
            ->method('addWishlistProduct')
            ->with($this->wishlistProduct);

        $this->facade->createWithProductVariant($this->wishlist, $productVariant);
    }

    public function testShouldCreateWishlistProductAndAddItToWishlist(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $this->wishlistProductFactory
            ->expects($this->once())
            ->method('createForWishlistAndProduct')
            ->with($this->wishlist, $product)
            ->willReturn($this->wishlistProduct);
        $this->wishlist
            ->expects($this->once())
            ->method('addWishlistProduct')
            ->with($this->wishlistProduct);

        $this->facade->createWithProduct($this->wishlist, $product);
    }
}
