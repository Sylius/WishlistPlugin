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

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\ShopUserWishlistResolver;
use Sylius\WishlistPlugin\Resolver\ShopUserWishlistResolverInterface;

final class ShopUserWishlistResolverTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistFactoryInterface $wishlistFactory;

    private MockObject&ChannelContextInterface $channelContext;

    private MockObject&ChannelInterface $channel;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&ShopUserInterface $user;

    private ShopUserWishlistResolver $resolver;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistFactory = $this->createMock(WishlistFactoryInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->user = $this->createMock(ShopUserInterface::class);
        $this->resolver = new ShopUserWishlistResolver(
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->channelContext,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(ShopUserWishlistResolver::class, $this->resolver);
    }

    public function testShouldImplementShopUserWishlistResolverInterface(): void
    {
        $this->assertInstanceOf(ShopUserWishlistResolverInterface::class, $this->resolver);
    }

    public function testShouldCreateNewWishlistForShopUserIfCannotResolveWithChannel(): void
    {
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUserAndChannel')->with($this->user, $this->channel)->willReturn(null);
        $this->wishlistFactory->expects($this->once())->method('createForUserAndChannel')->with($this->user, $this->channel)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->resolver->resolve($this->user),
        );
    }

    public function testShouldResolveWishlistForShopUserWithChannel(): void
    {
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($this->channel);
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUserAndChannel')->with($this->user, $this->channel)->willReturn($this->wishlist);
        $this->wishlistFactory->expects($this->never())->method('createForUserAndChannel')->with($this->user, $this->channel)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->resolver->resolve($this->user),
        );
    }

    public function testShouldCreateNewWishlistForShopUserIfCannotResolveWithoutChannel(): void
    {
        $this->channelContext->expects($this->once())->method('getChannel')->willThrowException(new ChannelNotFoundException());
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUser')->with($this->user)->willReturn(null);
        $this->wishlistFactory->expects($this->once())->method('createForUser')->with($this->user)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->resolver->resolve($this->user),
        );
    }

    public function testShouldResolveWishlistForShopUserWihtoutChannel(): void
    {
        $this->channelContext->expects($this->once())->method('getChannel')->willThrowException(new ChannelNotFoundException());
        $this->wishlistRepository->expects($this->once())->method('findOneByShopUser')->with($this->user)->willReturn($this->wishlist);
        $this->wishlistFactory->expects($this->never())->method('createForUser')->with($this->user)->willReturn($this->wishlist);

        $this->assertSame(
            $this->wishlist,
            $this->resolver->resolve($this->user),
        );
    }
}
