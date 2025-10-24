<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Command\Wishlist\CreateWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Resolver\ShopUserWishlistResolverInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CreateWishlistHandlerTest extends TestCase
{
    private MockObject&TokenStorageInterface $tokenStorage;

    private MockObject&WishlistFactoryInterface $wishlistFactory;

    private MockObject&ShopUserWishlistResolverInterface $shopUserWishlistResolver;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&ChannelRepositoryInterface $channelRepository;

    private MockObject&TokenUserResolverInterface $tokenUserResolver;

    private MockObject&RequestStack $requestStack;

    private MockObject&ShopUserInterface $user;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&ChannelInterface $channel;

    private MockObject&Request $request;

    private MockObject&ParameterBag $attributes;

    private CreateWishlist $command;

    private CreateWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->wishlistFactory = $this->createMock(WishlistFactoryInterface::class);
        $this->shopUserWishlistResolver = $this->createMock(ShopUserWishlistResolverInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
        $this->tokenUserResolver = $this->createMock(TokenUserResolverInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->user = $this->createMock(ShopUserInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->attributes = $this->createMock(ParameterBag::class);
        $this->request->attributes = $this->attributes;
        $this->command = new CreateWishlist(
            'test_token_value',
            'test_channel_code',
        );
        $this->handler = new CreateWishlistHandler(
            $this->tokenStorage,
            $this->wishlistFactory,
            $this->shopUserWishlistResolver,
            $this->wishlistManager,
            $this->channelRepository,
            $this->tokenUserResolver,
            $this->requestStack,
            'token',
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(CreateWishlistHandler::class, $this->handler);
    }

    public function testShouldCreateNewWishlistForUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenUserResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($token)
            ->willReturn($this->user);
        $this->wishlistFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($this->wishlist);
        $this->shopUserWishlistResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($this->user)
            ->willReturn($this->wishlist);
        $this->wishlist
            ->expects($this->once())
            ->method('setName')
            ->with('Wishlist');
        $this->wishlist
            ->expects($this->once())
            ->method('setToken')
            ->with('test_token_value');
        $this->requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($this->request);
        $this->channelRepository
            ->expects($this->once())
            ->method('findOneByCode')
            ->with('test_channel_code')
            ->willReturn($this->channel);
        $this->wishlist
            ->expects($this->once())
            ->method('setChannel')
            ->with($this->channel);
        $this->wishlistManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->wishlist);
        $this->wishlistManager
            ->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($this->command);
    }

    public function testShouldCreateNewWishlistForGuestWithMissingChannel(): void
    {
        $this->command->channelCode = null;

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $this->tokenUserResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(null)
            ->willReturn(null);
        $this->wishlistFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($this->wishlist);
        $this->shopUserWishlistResolver
            ->expects($this->never())
            ->method('resolve')
            ->with($this->user);
        $this->wishlist
            ->expects($this->once())
            ->method('setName')
            ->with('Wishlist');
        $this->wishlist
            ->expects($this->once())
            ->method('setToken')
            ->with('test_token_value');
        $this->requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($this->request);
        $this->channelRepository
            ->expects($this->never())
            ->method('findOneByCode')
            ->with('test');
        $this->wishlist
            ->expects($this->never())
            ->method('setChannel')
            ->with($this->channel);
        $this->wishlistManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->wishlist);
        $this->wishlistManager
            ->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($this->command);
    }
}
