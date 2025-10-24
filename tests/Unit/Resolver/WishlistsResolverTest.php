<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolver;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class WishlistsResolverTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&TokenStorageInterface $tokenStorage;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&ChannelContextInterface $channelContext;

    private MockObject&TokenUserResolverInterface $tokenUserResolver;

    private MockObject&MessageBusInterface $messageBus;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&TokenInterface $token;

    private MockObject&ChannelInterface $channel;

    private WishlistsResolver $resolver;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->tokenUserResolver = $this->createMock(TokenUserResolverInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->resolver = new WishlistsResolver(
            $this->wishlistRepository,
            $this->tokenStorage,
            $this->wishlistCookieTokenResolver,
            $this->channelContext,
            $this->tokenUserResolver,
            $this->messageBus,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(WishlistsResolver::class, $this->resolver);
    }

    public function testShouldImplementsWishlistsResolverInterface(): void
    {
        $this->assertInstanceOf(WishlistsResolverInterface::class, $this->resolver);
    }

    public function testShouldResolveWishlistsByShopUserAndToken(): void
    {
        $wishlistToken = 'wishlist_token';
        $user = $this->createMock(ShopUserInterface::class);

        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn($user);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn($wishlistToken);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $user->expects($this->once())->method('getId')->willReturn(1);
        $this->wishlistRepository->expects($this->once())->method('findAllByShopUserAndToken')->with(1, $wishlistToken)->willReturn([$this->wishlist]);

        $this->assertSame(
            [$this->wishlist],
            $this->resolver->resolve(),
        );
    }

    public function testShouldResolveWishlistsByTokenAndChannelWithoutUser(): void
    {
        $wishlistToken = 'wishlist_token';

        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn(null);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn($wishlistToken);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymousAndChannel')->with($wishlistToken, $this->channel)->willReturn([$this->wishlist]);

        $this->assertSame(
            [$this->wishlist],
            $this->resolver->resolve(),
        );
    }

    public function testShouldResolveWishlistsByToken(): void
    {
        $wishlistToken = 'wishlist_token';
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn(null);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn($wishlistToken);
        $this->channelContext->expects($this->once())->method('getChannel');
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymous')->with($wishlistToken)->willReturn([$this->wishlist]);

        $this->assertSame(
            [$this->wishlist],
            $this->resolver->resolve(),
        );
    }
}
