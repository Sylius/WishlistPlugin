<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\Generator\ModelCreatorInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;
use Sylius\WishlistPlugin\Processor\VariantPdfModelProcessor;
use Sylius\WishlistPlugin\Processor\VariantPdfModelProcessorInterface;

final class VariantPdfModelProcessorTest extends TestCase
{
    private MockObject&ModelCreatorInterface $pdfModelCreator;

    private VariantPdfModelProcessor $processor;

    protected function setUp(): void
    {
        $this->pdfModelCreator = $this->createMock(ModelCreatorInterface::class);
        $this->processor = new VariantPdfModelProcessor(
            $this->pdfModelCreator,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(VariantPdfModelProcessor::class, $this->processor);
    }

    public function testShouldImplementVariantPdfModelProcessorInterface(): void
    {
        $this->assertInstanceOf(VariantPdfModelProcessorInterface::class, $this->processor);
    }

    public function testShouldReturnCollectionOfPdfModel(): void
    {
        $firstWishlistItem = $this->createMock(WishlistItemInterface::class);
        $secondWishlistItem = $this->createMock(WishlistItemInterface::class);
        $firstPdfModel = $this->createMock(VariantPdfModelInterface::class);
        $secondPdfModel = $this->createMock(VariantPdfModelInterface::class);
        $this->pdfModelCreator->expects($this->exactly(2))->method('createWishlistItemToPdf')->willReturnMap([[$firstWishlistItem, $firstPdfModel], [$secondWishlistItem, $secondPdfModel]]);

        $this->assertSame(
            $firstPdfModel,
            $this->processor->createVariantPdfModelCollection(
                new ArrayCollection([$firstWishlistItem, $secondWishlistItem]),
            )->first(),
        );
    }

    public function testShouldReturnEmptyCollectionIfParameterIsEmtpy(): void
    {
        $this->assertSame(
            0,
            $this->processor->createVariantPdfModelCollection(new ArrayCollection())->count(),
        );
    }
}
