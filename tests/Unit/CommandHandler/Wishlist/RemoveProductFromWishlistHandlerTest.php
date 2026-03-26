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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductFromWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\RemoveProductFromWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;

final class RemoveProductFromWishlistHandlerTest extends TestCase
{
    private MockObject&ProductRepositoryInterface $productRepository;

    private MockObject&ObjectManager $wishlistManager;

    private RemoveProductFromWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->handler = new RemoveProductFromWishlistHandler(
            $this->productRepository,
            $this->wishlistManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveProductFromWishlistHandler::class, $this->handler);
    }

    public function testShouldRemoveProductFromWishlist(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $wishlistProduct->method('getProduct')->willReturn($product);

        $wishlist->method('getWishlistProducts')->willReturn(new ArrayCollection([$wishlistProduct]));

        $this->productRepository->expects($this->once())->method('find')->with(1)->willReturn($product);
        $wishlist->expects($this->once())->method('removeProduct')->with($wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('flush');

        $command = new RemoveProductFromWishlist(1);
        $command->setWishlist($wishlist);

        $this->assertSame($wishlist, ($this->handler)($command));
    }

    public function testShouldThrowExceptionWhenProductNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $wishlist = $this->createMock(WishlistInterface::class);

        $this->productRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        $command = new RemoveProductFromWishlist(1);
        $command->setWishlist($wishlist);

        ($this->handler)($command);
    }
}
