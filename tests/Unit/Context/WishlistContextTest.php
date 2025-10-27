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

namespace Tests\Sylius\WishlistPlugin\Unit\Context;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Context\WishlistContext;
use Sylius\WishlistPlugin\Context\WishlistContextInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class WishlistContextTest extends TestCase
{
    private MockObject&TokenStorageInterface $tokenStorage;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistFactoryInterface $wishlistFactory;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&ChannelContextInterface $channelContext;

    private MockObject&TokenUserResolverInterface $tokenUserResolver;

    private MockObject&ChannelInterface $channel;

    private MockObject&ShopUserInterface $shopUser;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&TokenInterface $token;

    private MockObject&Request $request;

    private WishlistContext $context;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistFactory = $this->createMock(WishlistFactoryInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->tokenUserResolver = $this->createMock(TokenUserResolverInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->shopUser = $this->createMock(ShopUserInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->context = new WishlistContext(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistCookieTokenResolver,
            $this->channelContext,
            $this->tokenUserResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistContext::class, $this->context);
    }

    public function testShouldImplementWishlistContextInterface(): void
    {
        $this->assertInstanceOf(WishlistContextInterface::class, $this->context);
    }

    public function testShouldCreateNewWishlistIfNoCookieAndUser(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('nonexistent-token');
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn(null);

        $this->wishlistFactory->expects($this->once())->method('createNew')->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->context->getWishlist($this->request),
        );
    }

    public function testShouldReturnCookieWishlistIfCookieAndNoUser(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('Fq8N4W6mk12i9J2HX0U60POGG5UEzSgGW37OWd6sv2dd8FlBId');
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn(null);
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('Fq8N4W6mk12i9J2HX0U60POGG5UEzSgGW37OWd6sv2dd8FlBId')->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->context->getWishlist($this->request),
        );
    }

    public function testShouldReturnNewWishlistIfCookieNotFoundAndNoUser(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('Fq8N4W6mk12i9J2HX0U60POGG5UEzSgGW37OWd6sv2dd8FlBId');
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn(null);
        $this->wishlistRepository->expects($this->once())->method('findByToken')->with('Fq8N4W6mk12i9J2HX0U60POGG5UEzSgGW37OWd6sv2dd8FlBId')->willReturn(null);
        $this->wishlistFactory->expects($this->once())->method('createNew')->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->context->getWishlist($this->request),
        );
    }

    public function testShouldReturnUserWishlistIfFoundAndUserIsLoggedIn(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('nonexistent-token');
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn($this->shopUser);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUserAndChannel')->with($this->shopUser, $this->channel)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->context->getWishlist($this->request),
        );
    }

    public function testShouldReturnNewWishlistIfNotFoundAndUserIsLoggedIn(): void
    {
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('nonexistent-token');
        $this->wishlistFactory->expects($this->once())->method('createNew')->willReturn($this->wishlist);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn($this->shopUser);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUserAndChannel')->with($this->shopUser, $this->channel)->willReturn(null);
        $this->wishlistFactory->expects($this->once())->method('createForUserAndChannel')->with($this->shopUser, $this->channel)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->context->getWishlist($this->request),
        );
    }
}
