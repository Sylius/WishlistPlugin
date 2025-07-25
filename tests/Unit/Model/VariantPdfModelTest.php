<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Model;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModel;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;

final class VariantPdfModelTest extends TestCase
{
    private VariantPdfModel $model;

    protected function setUp(): void
    {
        $this->model = new VariantPdfModel();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(VariantPdfModel::class, $this->model);
    }

    public function testShouldImplementVariantPdfModelInterface(): void
    {
        $this->assertInstanceOf(VariantPdfModelInterface::class, $this->model);
    }

    public function testShouldReturnPropertyOfVariantPdfModel(): void
    {
        $productVariant = $this->createMock(ProductVariantInterface::class);

        $this->model->setActualVariant('variant test');
        $this->model->setVariant($productVariant);
        $this->model->setImagePath('/image/123/image.jpg');
        $this->model->setQuantity(10);

        $this->assertSame('variant test', $this->model->getActualVariant());
        $this->assertSame($productVariant, $this->model->getVariant());
        $this->assertSame('/image/123/image.jpg', $this->model->getImagePath());
        $this->assertSame(10, $this->model->getQuantity());
    }
}
