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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Command\Wishlist\ExportSelectedProductsFromWishlistToPdfInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\ExportSelectedProductsFromWishlistToPdfHandler;
use Sylius\WishlistPlugin\Exporter\WishlistToPdfExporterInterface;

final class ExportSelectedProductsFromWishlistToPdfHandlerTest extends TestCase
{
    private MockObject&WishlistToPdfExporterInterface $exporterWishlistToPdf;

    private ExportSelectedProductsFromWishlistToPdfHandler $handler;

    protected function setUp(): void
    {
        $this->exporterWishlistToPdf = $this->createMock(WishlistToPdfExporterInterface::class);
        $this->handler = new ExportSelectedProductsFromWishlistToPdfHandler(
            $this->exporterWishlistToPdf,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(ExportSelectedProductsFromWishlistToPdfHandler::class, $this->handler);
    }

    public function testShouldExportCollectionOfProducts(): void
    {
        $command = $this->createMock(ExportSelectedProductsFromWishlistToPdfInterface::class);
        $wishlistProducts = $this->createMock(ArrayCollection::class);

        $command->expects($this->once())->method('getWishlistProducts')->willReturn($wishlistProducts);
        $this->exporterWishlistToPdf->expects($this->once())
            ->method('createModelToPdfAndExportToPdf')
            ->with($wishlistProducts)
            ->willReturn('%PDF-content%');

        $result = ($this->handler)($command);

        $this->assertSame('%PDF-content%', $result);
    }
}
