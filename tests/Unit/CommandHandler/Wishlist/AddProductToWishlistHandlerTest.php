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
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductToWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductToWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;

final class AddProductToWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&ProductRepositoryInterface $productRepository;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&ProductInterface $product;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private AddProductToWishlist $command;

    private AddProductToWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->product = $this->createMock(ProductInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->command = new AddProductToWishlist(1);
        $this->command->setWishlist($this->wishlist);
        $this->handler = new AddProductToWishlistHandler(
            $this->wishlistProductFactory,
            $this->productRepository,
            $this->wishlistManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductToWishlistHandler::class, $this->handler);
    }

    public function testShouldAddProductToWishlist(): void
    {
        $this->productRepository->expects($this->once())->method('find')->with(1)->willReturn($this->product);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndProduct')->with($this->wishlist, $this->product)->willReturn($this->wishlistProduct);
        $this->wishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('persist')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('flush');

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionIfProductIsNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);
        $this->productRepository->expects($this->once())->method('find')->with(1)->willReturn(null);
        $this->wishlistProductFactory->expects($this->never())->method('createForWishlistAndProduct')->with($this->wishlist, $this->product)->willReturn($this->wishlistProduct);
        $this->wishlist->expects($this->never())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->never())->method('persist')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->never())->method('flush');

        ($this->handler)($this->command);
    }
}
