<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Model\Factory\VariantPdfModelFactory;
use Sylius\WishlistPlugin\Model\Factory\VariantPdfModelFactoryInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;

final class VariantPdfModelFactoryTest extends TestCase
{
    private MockObject&ProductVariantInterface $productVariant;

    private VariantPdfModelFactory $factory;

    protected function setUp(): void
    {
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->factory = new VariantPdfModelFactory();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(VariantPdfModelFactory::class, $this->factory);
    }

    public function testShouldImplementVariantPdfModelFactoryInterface(): void
    {
        $this->assertInstanceOf(VariantPdfModelFactoryInterface::class, $this->factory);
    }

    public function testShouldCreateVariantPdfModel(): void
    {
        $productPdfModel = $this->factory->createWithVariantAndImagePath(
            $this->productVariant,
            'http://127.0.0.1:8000/media/image/b4/c2/fc6b3202ee567e0fb05f293b709c.jpg',
            10,
            'variant test',
        );

        $this->assertInstanceOf(VariantPdfModelInterface::class, $productPdfModel);
    }

    public function testShouldReturnVariantPdfModelData(): void
    {
        $productPdfModel = $this->factory->createWithVariantAndImagePath(
            $this->productVariant,
            'http://127.0.0.1:8000/media/image/b4/c2/fc6b3202ee567e0fb05f293b709c.jpg',
            10,
            'variant test',
        );

        $this->assertSame(
            $this->productVariant,
            $productPdfModel->getVariant(),
        );
        $this->assertSame(
            'http://127.0.0.1:8000/media/image/b4/c2/fc6b3202ee567e0fb05f293b709c.jpg',
            $productPdfModel->getImagePath(),
        );
        $this->assertSame(
            10,
            $productPdfModel->getQuantity(),
        );
        $this->assertSame(
            'variant test',
            $productPdfModel->getActualVariant(),
        );
    }
}
