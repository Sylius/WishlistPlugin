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

namespace Tests\Sylius\WishlistPlugin\Unit\Checker;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\WishlistPlugin\Checker\WishlistProductChecker;
use Sylius\WishlistPlugin\Checker\WishlistProductCheckerInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;

final class WishlistProductCheckerTest extends TestCase
{
    private MockObject&ProductInterface $product;

    private WishlistProductChecker $checker;

    protected function setUp(): void
    {
        $this->product = $this->createMock(ProductInterface::class);
        $this->checker = new WishlistProductChecker();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistProductChecker::class, $this->checker);
    }

    public function testShouldImplementsWishlistProductCheckerInterface(): void
    {
        $this->assertInstanceOf(WishlistProductCheckerInterface::class, $this->checker);
    }

    public function testShouldReturnTrueWhenProductIsInOneOfTheWishlists(): void
    {
        $wishlistOne = $this->createMock(WishlistInterface::class);
        $wishlistTwo = $this->createMock(WishlistInterface::class);

        $wishlistOne->expects($this->once())->method('hasProduct')->with($this->product)->willReturn(false);
        $wishlistTwo->expects($this->once())->method('hasProduct')->with($this->product)->willReturn(true);

        $this->assertTrue(
            $this->checker->isProductInAnyWishlist($this->product, [$wishlistOne, $wishlistTwo]),
        );
    }

    public function testShouldReturnFalseWhenProductIsNotInAnyWishlist(): void
    {
        $wishlistOne = $this->createMock(WishlistInterface::class);
        $wishlistTwo = $this->createMock(WishlistInterface::class);

        $wishlistOne->expects($this->once())->method('hasProduct')->with($this->product)->willReturn(false);
        $wishlistTwo->expects($this->once())->method('hasProduct')->with($this->product)->willReturn(false);

        $this->assertFalse(
            $this->checker->isProductInAnyWishlist($this->product, [$wishlistOne, $wishlistTwo]),
        );
    }

    public function testShouldReturnFalseWhenWishlistsArrayIsEmpty(): void
    {
        $this->assertFalse(
            $this->checker->isProductInAnyWishlist($this->product, []),
        );
    }
}
