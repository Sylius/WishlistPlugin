<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Factory;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactory;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;

final class WishlistProductFactoryTest extends TestCase
{
    private MockObject&FactoryInterface $innerFactory;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&ProductInterface $product;

    private MockObject&ProductVariantInterface $productVariant;

    private WishlistProductFactory $factory;

    protected function setUp(): void
    {
        $this->innerFactory = $this->createMock(FactoryInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->product = $this->createMock(ProductInterface::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->factory = new WishlistProductFactory($this->innerFactory);
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistProductFactory::class, $this->factory);
    }

    public function testShouldImplementWishlistProductFactoryInterface(): void
    {
        $this->assertInstanceOf(WishlistProductFactoryInterface::class, $this->factory);
    }

    public function testShouldCreateWishlistProduct(): void
    {
        $this->innerFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($this->wishlistProduct);

        $this->assertSame(
            $this->wishlistProduct,
            $this->factory->createNew(),
        );
    }

    public function testShouldCreateWishlistProductForWishlistAndProduct(): void
    {
        $productVariants = $this->createMock(Collection::class);
        $this->product
            ->expects($this->once())
            ->method('getVariants')
            ->willReturn($productVariants);
        $productVariants
            ->expects($this->once())
            ->method('first')
            ->willReturn($this->productVariant);
        $this->innerFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($this->wishlistProduct);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setWishlist')
            ->with($this->wishlist);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setProduct')
            ->with($this->product);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setVariant')
            ->with($this->productVariant);

        $this->assertSame(
            $this->wishlistProduct,
            $this->factory->createForWishlistAndProduct($this->wishlist, $this->product),
        );
    }

    public function testShouldCreateWishlistProductForWishlistAndVariant(): void
    {
        $this->productVariant
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $this->innerFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($this->wishlistProduct);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setWishlist')
            ->with($this->wishlist);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setProduct')
            ->with($this->product);
        $this->wishlistProduct
            ->expects($this->once())
            ->method('setVariant')
            ->with($this->productVariant);

        $this->assertSame(
            $this->wishlistProduct,
            $this->factory->createForWishlistAndVariant($this->wishlist, $this->productVariant),
        );
    }
}
