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

namespace Tests\Sylius\WishlistPlugin\Unit\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Factory\WishlistFactory;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;

final class WishlistFactoryTest extends TestCase
{
    private MockObject&FactoryInterface $innerFactory;

    private MockObject&WishlistInterface $wishlist;

    private WishlistFactory $factory;

    protected function setUp(): void
    {
        $this->innerFactory = $this->createMock(FactoryInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->factory = new WishlistFactory($this->innerFactory);
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistFactory::class, $this->factory);
    }

    public function testShouldImplementWishlistFactoryInterface(): void
    {
        $this->assertInstanceOf(WishlistFactoryInterface::class, $this->factory);
    }

    public function testShouldCreateNewWishlist(): void
    {
        $this->innerFactory->expects($this->once())->method('createNew')->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->factory->createNew(),
        );
    }

    public function testShouldCreateWishlistForUser(): void
    {
        $shopUser = $this->createMock(ShopUserInterface::class);

        $this->innerFactory->expects($this->once())->method('createNew')->willReturn($this->wishlist);
        $this->wishlist->expects($this->once())->method('setShopUser')->with($shopUser);

        $this->assertSame(
            $this->wishlist,
            $this->factory->createForUser($shopUser),
        );
    }
}
