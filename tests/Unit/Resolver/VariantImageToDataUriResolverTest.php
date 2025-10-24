<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Resolver\GenerateDataUriForImageResolverInterface;
use Sylius\WishlistPlugin\Resolver\VariantImageToDataUriResolver;
use Sylius\WishlistPlugin\Resolver\VariantImageToDataUriResolverInterface;

final class VariantImageToDataUriResolverTest extends TestCase
{
    private const TEST_BASE_URL = 'http://test:8000';

    private MockObject&GenerateDataUriForImageResolverInterface $generateDataUriForImageResolver;

    private MockObject&ProductVariantInterface $variant;

    private MockObject&ProductInterface $product;

    private MockObject&Collection $productImages;

    private VariantImageToDataUriResolver $resolver;

    protected function setUp(): void
    {
        $this->generateDataUriForImageResolver = $this->createMock(GenerateDataUriForImageResolverInterface::class);
        $this->variant = $this->createMock(ProductVariantInterface::class);
        $this->product = $this->createMock(ProductInterface::class);
        $this->productImages = $this->createMock(Collection::class);
        $this->resolver = new VariantImageToDataUriResolver(
            $this->generateDataUriForImageResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(VariantImageToDataUriResolver::class, $this->resolver);
    }

    public function testShouldImplementVariantImageToDataUriResolverInterface(): void
    {
        $this->assertInstanceOf(VariantImageToDataUriResolverInterface::class, $this->resolver);
    }

    public function testShouldResolveEmptyImagePath(): void
    {
        $this->variant
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $this->product
            ->expects($this->once())
            ->method('getImages')
            ->willReturn($this->productImages);
        $this->productImages
            ->expects($this->once())
            ->method('first')
            ->willReturn(false);
        $this->generateDataUriForImageResolver
            ->expects($this->once())
            ->method('resolveWithNoImage')
            ->willReturn(self::TEST_BASE_URL);

        $this->assertSame(
            self::TEST_BASE_URL,
            $this->resolver->resolve($this->variant, self::TEST_BASE_URL),
        );
    }

    public function testShouldResolveImagePath(): void
    {
        $productImage = $this->createMock(ProductImageInterface::class);
        $this->variant
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $this->product
            ->expects($this->once())
            ->method('getImages')
            ->willReturn($this->productImages);
        $this->productImages
            ->expects($this->once())
            ->method('first')
            ->willReturn($productImage);
        $productImage
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('test.jpg');
        $this->generateDataUriForImageResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($productImage)
            ->willReturn(self::TEST_BASE_URL);

        $this->assertSame(
            self::TEST_BASE_URL,
            $this->resolver->resolve($this->variant, self::TEST_BASE_URL),
        );
    }
}
