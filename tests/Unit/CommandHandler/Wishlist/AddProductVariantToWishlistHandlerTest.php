<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductVariantToWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductVariantToWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\ProductVariantNotFoundException;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;

final class AddProductVariantToWishlistHandlerTest extends TestCase
{
    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&ProductVariantInterface $productVariant;

    private MockObject&WishlistInterface $wishlist;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private AddProductVariantToWishlist $command;

    private AddProductVariantToWishlistHandler $handler;

    protected function setUp(): void
    {
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->command = new AddProductVariantToWishlist(1);
        $this->command->setWishlist($this->wishlist);
        $this->handler = new AddProductVariantToWishlistHandler(
            $this->wishlistProductFactory,
            $this->productVariantRepository,
            $this->wishlistManager,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductVariantToWishlistHandler::class, $this->handler);
    }

    public function testShouldAddProductVariantToWishlist(): void
    {
        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn($this->productVariant);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->wishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->wishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->once())->method('persist')->with($this->wishlist);
        $this->wishlistManager->expects($this->once())->method('flush');

        ($this->handler)($this->command);
    }

    public function testShouldThrowExceptionIfProductVariantIsNotFound(): void
    {
        $this->expectException(ProductVariantNotFoundException::class);
        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn(null);
        $this->wishlistProductFactory->expects($this->never())->method('createForWishlistAndVariant')->with($this->wishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->wishlist->expects($this->never())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistManager->expects($this->never())->method('persist')->with($this->wishlist);
        $this->wishlistManager->expects($this->never())->method('flush');

        ($this->handler)($this->command);
    }
}
