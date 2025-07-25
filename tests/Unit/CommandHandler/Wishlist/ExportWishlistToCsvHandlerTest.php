<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\OrderBundle\Controller\AddToCartCommandInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Command\Wishlist\ExportWishlistToCsv;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\ExportWishlistToCsvHandler;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\CsvSerializerFactoryInterface;
use Sylius\WishlistPlugin\Factory\CsvWishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Model\DTO\CsvWishlistProductInterface;
use Symfony\Component\Serializer\Serializer;

final class ExportWishlistToCsvHandlerTest extends TestCase
{
    private MockObject&CsvWishlistProductFactoryInterface $csvWishlistProductFactory;

    private MockObject&CsvSerializerFactoryInterface $csvSerializerFactory;

    private ExportWishlistToCsvHandler $handler;

    protected function setUp(): void
    {
        $this->csvWishlistProductFactory = $this->createMock(CsvWishlistProductFactoryInterface::class);
        $this->csvSerializerFactory = $this->createMock(CsvSerializerFactoryInterface::class);
        $this->handler = new ExportWishlistToCsvHandler(
            $this->csvWishlistProductFactory,
            $this->csvSerializerFactory,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(ExportWishlistToCsvHandler::class, $this->handler);
    }

    public function testShouldExportWishlistToCsv(): void
    {
        $wishlistItem = $this->createMock(WishlistItemInterface::class);
        $file = $this->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['php://memory', 'w+'])
            ->getMock();
        $addToCartCommand = $this->createMock(AddToCartCommandInterface::class);
        $orderItem = $this->createMock(OrderItemInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $productVariant = $this->createMock(ProductVariantInterface::class);
        $product = $this->createMock(ProductInterface::class);
        $csvWishlistProduct = $this->createMock(CsvWishlistProductInterface::class);
        $serializer = $this->createMock(Serializer::class);

        $fputcsvInvokeCount = $this->exactly(2);
        $file
            ->expects($fputcsvInvokeCount)
            ->method('fputcsv')
            ->willReturnCallback(function ($value) use ($fputcsvInvokeCount) {
                if (1 === $fputcsvInvokeCount->numberOfInvocations()) {
                    $this->assertSame(ExportWishlistToCsvHandler::CSV_HEADERS, $value);
                }
                if (2 === $fputcsvInvokeCount->numberOfInvocations()) {
                    $this->assertSame(['serializer_result'], $value);
                }

                return 0;
            });
        $wishlistItem
            ->method('getCartItem')
            ->willReturn($addToCartCommand);
        $addToCartCommand
            ->expects($this->once())
            ->method('getCartItem')
            ->willReturn($orderItem);
        $wishlistItem
            ->method('getWishlistProduct')
            ->willReturn($wishlistProduct);
        $orderItem
            ->method('getVariant')
            ->willReturn($productVariant);
        $productVariant
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $productVariant
            ->expects($this->once())
            ->method('getCode')
            ->willReturn('test_product_variant');
        $wishlistProduct
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $product
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->csvWishlistProductFactory
            ->expects($this->once())
            ->method('createWithProperties')
            ->with(1, 1, 'test_product_variant')
            ->willReturn($csvWishlistProduct);
        $this->csvSerializerFactory
            ->expects($this->once())
            ->method('createNew')
            ->willReturn($serializer);
        $serializer
            ->expects($this->once())
            ->method('normalize')
            ->with($csvWishlistProduct, 'csv')
            ->willReturn(['serializer_result']);

        $this->handler->__invoke(
            new ExportWishlistToCsv(new ArrayCollection([$wishlistItem]), $file),
        );
    }
}
