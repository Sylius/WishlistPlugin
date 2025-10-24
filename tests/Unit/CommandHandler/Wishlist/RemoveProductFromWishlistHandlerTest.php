<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\ResourceActions;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductFromWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductFromWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class RemoveProductFromWishlistHandlerTest extends TestCase
{
    private MockObject&ProductRepositoryInterface $productRepository;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&RepositoryInterface $wishlistProductRepository;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&AuthorizationCheckerInterface $authorizationChecker;

    private MockObject&ProductInterface $product;

    private RemoveProductFromWishlist $command;

    private RemoveProductFromWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistProductRepository = $this->createMock(RepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->product = $this->createMock(ProductInterface::class);
        $this->command = new RemoveProductFromWishlist(1, 'wishlist_token');
        $this->handler = new RemoveProductFromWishlistHandler(
            $this->productRepository,
            $this->wishlistRepository,
            $this->wishlistProductRepository,
            $this->wishlistManager,
            $this->authorizationChecker,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveProductFromWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveProductFromWishlist(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->product);
        $this->wishlistRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('wishlist_token')
            ->willReturn($wishlist);
        $this->wishlistProductRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['product' => $this->product, 'wishlist' => $wishlist])
            ->willReturn($wishlistProduct);
        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with(ResourceActions::DELETE, $wishlist)
            ->willReturn(true);
        $wishlist
            ->expects($this->once())
            ->method('removeProduct')
            ->with($wishlistProduct);
        $this->wishlistManager
            ->expects($this->once())
            ->method('flush');

        $this->assertSame(
            $wishlist,
            $this->handler->__invoke($this->command),
        );
    }

    public function testShouldThrowExceptionWhenProductNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->handler->__invoke($this->command);
    }

    public function testShouldThrowExceptionWhenWishlistNotFound(): void
    {
        $this->expectException(WishlistNotFoundException::class);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($this->product);
        $this->wishlistRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('wishlist_token')
            ->willReturn(null);

        $this->handler->__invoke($this->command);
    }
}
