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
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddWishlistToUser;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddWishlistToUserHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\WishlistHasAnotherShopUserException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;

final class AddWishlistToUserHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&ShopUserInterface $shopUser;

    private AddWishlistToUser $command;

    private AddWishlistToUserHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->shopUser = $this->createMock(ShopUserInterface::class);
        $this->command = new AddWishlistToUser(
            $this->wishlist,
            $this->shopUser,
        );
        $this->handler = new AddWishlistToUserHandler(
            $this->wishlistRepository,
            $this->wishlistCookieTokenResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddWishlistToUserHandler::class, $this->handler);
    }

    public function testShouldAddWishlistToUser(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlist->expects($this->once())->method('getToken')->willReturn('token');
        $this->wishlist->expects($this->exactly(2))->method('getName')->willReturn('Testing wishlist');
        $this->wishlist->expects($this->once())->method('getId')->willReturn(1);
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUserAndName')->with($this->shopUser, 'Testing wishlist')->willReturn($this->wishlist);
        $this->wishlist->expects($this->once())->method('setShopUser')->with($this->shopUser);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->wishlist);

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionIfTokensAreDifferent(): void
    {
        $this->expectException(WishlistHasAnotherShopUserException::class);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlist->expects($this->once())->method('getToken')->willReturn('differentToken');
        $this->wishlist->expects($this->never())->method('getName');
        $this->wishlist->expects($this->never())->method('getId');
        $this->wishlistRepository->expects($this->never())->method('findOneByShopUserAndName');
        $this->wishlist->expects($this->never())->method('setShopUser');
        $this->wishlistRepository->expects($this->never())->method('add');

        ($this->handler)($this->command);
    }
}
