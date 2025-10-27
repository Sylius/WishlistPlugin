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

namespace Tests\Sylius\WishlistPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Entity\Wishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;

final class WishlistTest extends TestCase
{
    private Wishlist $wishlist;

    protected function setUp(): void
    {
        $this->wishlist = new Wishlist();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(Wishlist::class, $this->wishlist);
    }

    public function testShouldImplementWishlistInterface(): void
    {
        $this->assertInstanceOf(WishlistInterface::class, $this->wishlist);
    }

    public function testShouldHaveNoProductsByDefault(): void
    {
        $this->assertEmpty($this->wishlist->getProducts()->toArray());
    }

    public function testShouldHaveNoWishlistProductsByDefault(): void
    {
        $this->assertEmpty($this->wishlist->getWishlistProducts()->toArray());
    }

    public function testShouldNotHaveProductByDefault(): void
    {
        $this->assertFalse($this->wishlist->hasProduct($this->createMock(ProductInterface::class)));
    }

    public function testShouldNotHaveWishlistProductByDefault(): void
    {
        $this->assertFalse($this->wishlist->hasWishlistProduct($this->createMock(WishlistProductInterface::class)));
    }

    public function testShouldAddWishlistProduct(): void
    {
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $productVariant = $this->createMock(ProductVariantInterface::class);
        $wishlistProduct->expects($this->once())->method('getVariant')->willReturn($productVariant);
        $wishlistProduct->expects($this->once())->method('setWishlist')->with($this->wishlist);

        $this->wishlist->addWishlistProduct($wishlistProduct);

        $this->assertTrue($this->wishlist->getWishlistProducts()->contains($wishlistProduct));
    }

    public function testShouldReturnShopUser(): void
    {
        $this->wishlist->setShopUser($this->createMock(ShopUserInterface::class));

        $this->assertInstanceOf(ShopUserInterface::class, $this->wishlist->getShopUser());
    }
}
