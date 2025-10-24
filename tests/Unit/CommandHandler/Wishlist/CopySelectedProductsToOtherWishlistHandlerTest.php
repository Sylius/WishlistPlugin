<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\CopySelectedProductsToOtherWishlistInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CopySelectedProductsToOtherWishlistHandler;
use Sylius\WishlistPlugin\Duplicator\WishlistProductsToOtherWishlistDuplicatorInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;

final class CopySelectedProductsToOtherWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistProductsToOtherWishlistDuplicatorInterface $copyistProductsToWishlist;

    private CopySelectedProductsToOtherWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->copyistProductsToWishlist = $this->createMock(WishlistProductsToOtherWishlistDuplicatorInterface::class);
        $this->handler = new CopySelectedProductsToOtherWishlistHandler(
            $this->wishlistRepository,
            $this->copyistProductsToWishlist,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(CopySelectedProductsToOtherWishlistHandler::class, $this->handler);
    }

    public function testShouldCopySelectedProductsToAnotherWishlist(): void
    {
        $command = $this->createMock(CopySelectedProductsToOtherWishlistInterface::class);
        $wishlistProducts = $this->createMock(ArrayCollection::class);
        $wishlist = $this->createMock(WishlistInterface::class);
        $command->expects($this->once())->method('getWishlistProducts')->willReturn($wishlistProducts);
        $command->expects($this->once())->method('getDestinedWishlistId')->willReturn(2);
        $this->wishlistRepository->expects($this->once())->method('find')->with(2)->willReturn($wishlist);
        $this->copyistProductsToWishlist->expects($this->once())->method('copyWishlistProductsToOtherWishlist')->with($wishlistProducts, $wishlist);

        ($this->handler)($command);
    }
}
