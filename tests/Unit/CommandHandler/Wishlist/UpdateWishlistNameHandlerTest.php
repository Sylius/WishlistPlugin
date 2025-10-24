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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\UpdateWishlistName;
use Sylius\WishlistPlugin\Command\Wishlist\UpdateWishlistNameInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\UpdateWishlistNameHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\WishlistNameIsTakenException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;

final class UpdateWishlistNameHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&WishlistInterface $wishlist;

    private UpdateWishlistNameInterface $command;

    private UpdateWishlistNameHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->command = new UpdateWishlistName(
            'newName',
            $this->wishlist,
        );
        $this->handler = new UpdateWishlistNameHandler(
            $this->wishlistRepository,
            $this->wishlistCookieTokenResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(UpdateWishlistNameHandler::class, $this->handler);
    }

    public function testShouldRenameFoundWishlist(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlistRepository->expects($this->once())->method('findOneByTokenAndName')->with('token', 'newName')->willReturn(null);
        $this->wishlist->expects($this->once())->method('setName')->with('newName');
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->wishlist);

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionWhenWishlistNameIsAlreadyTaken(): void
    {
        $this->expectException(WishlistNameIsTakenException::class);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlistRepository->expects($this->once())->method('findOneByTokenAndName')->with('token', 'newName')->willReturn($this->createMock(WishlistInterface::class));
        $this->wishlist->expects($this->never())->method('setName')->with('newName');
        $this->wishlistRepository->expects($this->never())->method('add')->with($this->wishlist);

        ($this->handler)($this->command);
    }
}
