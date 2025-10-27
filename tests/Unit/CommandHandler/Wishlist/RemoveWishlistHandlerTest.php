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

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;

final class RemoveWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&WishlistInterface $wishlist;

    private RemoveWishlist $command;

    private RemoveWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->command = new RemoveWishlist('token');
        $this->handler = new RemoveWishlistHandler(
            $this->wishlistRepository,
            $this->wishlistManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveMatchingWishlist(): void
    {
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('token')->willReturn($this->wishlist);
        $this->wishlistManager->expects($this->once())->method('remove')->with($this->wishlist);
        $this->wishlistManager->expects($this->once())->method('flush');

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionWhenWishlistIsNotFound(): void
    {
        $this->expectException(WishlistNotFoundException::class);
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('token')->willReturn(null);
        $this->wishlistManager->expects($this->never())->method('remove')->with($this->wishlist);
        $this->wishlistManager->expects($this->never())->method('flush');

        ($this->handler)($this->command);
    }
}
