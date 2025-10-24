<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProduct;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;

final class WishlistProductTest extends TestCase
{
    private WishlistProduct $wishlistProduct;

    protected function setUp(): void
    {
        $this->wishlistProduct = new WishlistProduct();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistProduct::class, $this->wishlistProduct);
    }

    public function testShouldImplementWishlistProductInterface(): void
    {
        $this->assertInstanceOf(WishlistProductInterface::class, $this->wishlistProduct);
    }

    public function testShouldReturnWishlist(): void
    {
        $this->wishlistProduct->setWishlist($this->createMock(WishlistInterface::class));

        $this->assertInstanceOf(WishlistInterface::class, $this->wishlistProduct->getWishlist());
    }

    public function testShouldReturnProduct(): void
    {
        $this->wishlistProduct->setProduct($this->createMock(ProductInterface::class));

        $this->assertInstanceOf(ProductInterface::class, $this->wishlistProduct->getProduct());
    }
}
