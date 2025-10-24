<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\Processor\SelectedWishlistProductsProcessor;
use Sylius\WishlistPlugin\Processor\SelectedWishlistProductsProcessorInterface;

final class SelectedWishlistProductsProcessorTest extends TestCase
{
    private SelectedWishlistProductsProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new SelectedWishlistProductsProcessor();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(SelectedWishlistProductsProcessor::class, $this->processor);
    }

    public function testShouldImplementSelectedWishlistProductsProcessorInterface(): void
    {
        $this->assertInstanceOf(SelectedWishlistProductsProcessorInterface::class, $this->processor);
    }

    public function testShouldReturnSelectedWishlistItems(): void
    {
        $firstWishlistItem = $this->createMock(WishlistItemInterface::class);
        $secondWishlistItem = $this->createMock(WishlistItemInterface::class);
        $firstWishlistItem->expects($this->once())->method('isSelected')->willReturn(false);
        $secondWishlistItem->expects($this->once())->method('isSelected')->willReturn(true);

        $this->assertSame(
            $secondWishlistItem,
            $this->processor->createSelectedWishlistProductsCollection(
                new ArrayCollection([$firstWishlistItem, $secondWishlistItem]),
            )->first(),
        );
    }

    public function testShouldReturnEmptyCollectionIfParameterIsEmpty(): void
    {
        $this->assertSame(
            0,
            $this->processor->createSelectedWishlistProductsCollection(new ArrayCollection())->count(),
        );
    }
}
