<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductToSelectedWishlistInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductToSelectedWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;

final class AddProductToSelectedWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private AddProductToSelectedWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->handler = new AddProductToSelectedWishlistHandler(
            $this->wishlistProductFactory,
            $this->wishlistRepository,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductToSelectedWishlistHandler::class, $this->handler);
    }

    public function testShouldAddProductToWishlistIfProductIsFound(): void
    {
        $command = $this->createMock(AddProductToSelectedWishlistInterface::class);
        $product = $this->createMock(ProductInterface::class);
        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);

        $command
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $command
            ->expects($this->once())
            ->method('getWishlist')
            ->willReturn($wishlist);
        $this->wishlistProductFactory
            ->expects($this->once())
            ->method('createForWishlistAndProduct')
            ->with($wishlist, $product)
            ->willReturn($wishlistProduct);
        $wishlist
            ->expects($this->once())
            ->method('addWishlistProduct')
            ->with($wishlistProduct);
        $this->wishlistRepository
            ->expects($this->once())
            ->method('add')
            ->with($wishlist);

        $this->handler->__invoke($command);
    }
}
