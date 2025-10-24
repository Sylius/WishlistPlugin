<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Command\Wishlist\CreateNewWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateNewWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\WishlistNameIsTakenException;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CreateNewWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&TokenStorageInterface $tokenStorage;

    private MockObject&WishlistFactoryInterface $wishlistFactory;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&ChannelRepositoryInterface $channelRepository;

    private MockObject&TokenUserResolverInterface $tokenUserResolver;

    private MockObject&TokenInterface $token;

    private MockObject&ShopUserInterface $shopUser;

    private MockObject&WishlistInterface $firstWishlist;

    private MockObject&WishlistInterface $secondWishlist;

    private MockObject&ChannelInterface $channel;

    private CreateNewWishlist $command;

    private CreateNewWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->wishlistFactory = $this->createMock(WishlistFactoryInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
        $this->tokenUserResolver = $this->createMock(TokenUserResolverInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->shopUser = $this->createMock(ShopUserInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->firstWishlist = $this->createMock(WishlistInterface::class);
        $this->secondWishlist = $this->createMock(WishlistInterface::class);
        $this->command = new CreateNewWishlist(
            'New wishlist',
            'test_channel_code',
        );
        $this->handler = new CreateNewWishlistHandler(
            $this->wishlistRepository,
            $this->tokenStorage,
            $this->wishlistFactory,
            $this->wishlistCookieTokenResolver,
            $this->channelRepository,
            $this->tokenUserResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(CreateNewWishlistHandler::class, $this->handler);
    }

    public function testShouldCreateNewWishlistForUser(): void
    {
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn($this->shopUser);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlistFactory->expects($this->once())->method('createForUser')->with($this->shopUser)->willReturn($this->firstWishlist);
        $this->firstWishlist->expects($this->once())->method('getShopUser')->willReturn($this->shopUser);
        $this->shopUser->expects($this->once())->method('getId')->willReturn(1);
        $this->wishlistRepository->expects($this->once())->method('findAllByShopUser')->with(1)->willReturn([$this->firstWishlist]);
        $this->firstWishlist->expects($this->once())->method('setToken')->with('token');
        $this->channelRepository->expects($this->once())->method('findOneByCode')->with('test_channel_code')->willReturn($this->channel);
        $this->firstWishlist->expects($this->once())->method('setChannel')->with($this->channel);
        $this->firstWishlist->expects($this->once())->method('setName')->with('New wishlist');
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn(1);

        ($this->handler)($this->command);
    }

    public function testShouldCreateNewWishlistForGuest(): void
    {
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn(null);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with(null)->willReturn(null);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlistFactory->expects($this->once())->method('createNew')->willReturn($this->firstWishlist);
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymous')->with('token')->willReturn([]);
        $this->firstWishlist->expects($this->once())->method('setToken')->with('token');
        $this->firstWishlist->expects($this->once())->method('setName')->with('New wishlist');
        $this->channelRepository->expects($this->once())->method('findOneByCode')->with('test_channel_code')->willReturn($this->channel);
        $this->firstWishlist->expects($this->once())->method('setChannel')->with($this->channel);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn(1);

        ($this->handler)($this->command);
    }

    public function testShouldThrowWishlistNameIsTakenExceptionIfWishlistNameIsDuplicatedForUser(): void
    {
        $this->expectException(WishlistNameIsTakenException::class);
        $this->command->name = 'existing';

        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($this->token);
        $this->tokenUserResolver->expects($this->once())->method('resolve')->with($this->token)->willReturn($this->shopUser);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('token');
        $this->wishlistFactory->expects($this->once())->method('createForUser')->with($this->shopUser)->willReturn($this->firstWishlist);
        $this->firstWishlist->expects($this->once())->method('getShopUser')->willReturn($this->shopUser);
        $this->shopUser->expects($this->once())->method('getId')->willReturn(1);
        $this->wishlistRepository->expects($this->once())->method('findAllByShopUser')->with(1)->willReturn([$this->secondWishlist]);
        $this->firstWishlist->expects($this->once())->method('setToken')->with('token');
        $this->channelRepository->expects($this->once())->method('findOneByCode')->with('test_channel_code')->willReturn($this->channel);
        $this->firstWishlist->expects($this->once())->method('setChannel')->with($this->channel);
        $this->secondWishlist->expects($this->once())->method('getName')->willReturn('existing');
        $this->firstWishlist->expects($this->never())->method('setName')->with('existing');
        $this->wishlistRepository->expects($this->never())->method('add')->with($this->firstWishlist);

        ($this->handler)($this->command);
    }
}
