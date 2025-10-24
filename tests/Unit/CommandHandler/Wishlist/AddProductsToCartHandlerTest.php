<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\CommandHandler\Wishlist;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\OrderBundle\Controller\AddToCartCommandInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductsToCartInterface;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddProductsToCartHandler;
use Sylius\WishlistPlugin\Exception\InsufficientProductStockException;
use Sylius\WishlistPlugin\Exception\InvalidProductQuantityException;

final class AddProductsToCartHandlerTest extends TestCase
{
    private MockObject&OrderModifierInterface $orderModifier;

    private MockObject&OrderRepositoryInterface $orderRepository;

    private MockObject&AvailabilityCheckerInterface $availabilityChecker;

    private MockObject&ProductVariantInterface $productVariant;

    private MockObject&OrderItemInterface $orderItem;

    private MockObject&WishlistItemInterface $wishlistItem;

    private MockObject&AddToCartCommandInterface $addToCartCommand;

    private MockObject&AddProductsToCartInterface $command;

    private AddProductsToCartHandler $handler;

    protected function setUp(): void
    {
        $this->orderModifier = $this->createMock(OrderModifierInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityChecker = $this->createMock(AvailabilityCheckerInterface::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->orderItem = $this->createMock(OrderItemInterface::class);
        $this->wishlistItem = $this->createMock(WishlistItemInterface::class);
        $this->addToCartCommand = $this->createMock(AddToCartCommandInterface::class);
        $this->command = $this->createMock(AddProductsToCartInterface::class);
        $this->handler = new AddProductsToCartHandler(
            $this->orderModifier,
            $this->orderRepository,
            $this->availabilityChecker,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductsToCartHandler::class, $this->handler);
    }

    public function testShouldAddProductsFromWishlistToCart(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->wishlistItem
            ->expects($this->exactly(2))
            ->method('getCartItem')
            ->willReturn($this->addToCartCommand);
        $this->addToCartCommand
            ->expects($this->exactly(2))
            ->method('getCartItem')
            ->willReturn($this->orderItem);
        $this->orderItem
            ->expects($this->once())
            ->method('getVariant')
            ->willReturn($this->productVariant);
        $this->orderItem
            ->expects($this->exactly(2))
            ->method('getQuantity')
            ->willReturn(1);
        $this->addToCartCommand
            ->expects($this->once())
            ->method('getCart')
            ->willReturn($order);
        $this->orderModifier
            ->expects($this->once())
            ->method('addToOrder')
            ->with($order, $this->orderItem);
        $this->orderRepository
            ->expects($this->once())
            ->method('add')
            ->with($order);
        $this->availabilityChecker
            ->expects($this->once())
            ->method('isStockSufficient')
            ->with($this->productVariant, 1)
            ->willReturn(true);
        $this->command
            ->expects($this->once())
            ->method('getWishlistProducts')
            ->willReturn(new ArrayCollection([$this->wishlistItem]));

        $this->handler->__invoke($this->command);
    }

    public function testShouldThrowExceptionWhenStockIsInsufficient(): void
    {
        $this->expectException(InsufficientProductStockException::class);

        $this->wishlistItem
            ->expects($this->once())
            ->method('getCartItem')
            ->willReturn($this->addToCartCommand);
        $this->addToCartCommand
            ->expects($this->once())
            ->method('getCartItem')
            ->willReturn($this->orderItem);
        $this->orderItem
            ->expects($this->once())
            ->method('getVariant')
            ->willReturn($this->productVariant);
        $this->orderItem
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn(1);
        $this->availabilityChecker
            ->expects($this->once())
            ->method('isStockSufficient')
            ->with($this->productVariant, 1)
            ->willReturn(false);
        $this->command
            ->expects($this->once())
            ->method('getWishlistProducts')
            ->willReturn(new ArrayCollection([$this->wishlistItem]));

        $this->handler->__invoke($this->command);
    }

    public function testShouldThrowExceptionWhenQuantityIsNotPositive(): void
    {
        $this->expectException(InvalidProductQuantityException::class);

        $this->wishlistItem
            ->expects($this->once())
            ->method('getCartItem')
            ->willReturn($this->addToCartCommand);
        $this->addToCartCommand
            ->expects($this->once())
            ->method('getCartItem')
            ->willReturn($this->orderItem);
        $this->orderItem
            ->expects($this->once())
            ->method('getVariant')
            ->willReturn($this->productVariant);
        $this->orderItem
            ->expects($this->exactly(2))
            ->method('getQuantity')
            ->willReturn(0);
        $this->availabilityChecker
            ->expects($this->once())
            ->method('isStockSufficient')
            ->with($this->productVariant, 0)
            ->willReturn(true);
        $this->command
            ->expects($this->once())
            ->method('getWishlistProducts')
            ->willReturn(new ArrayCollection([$this->wishlistItem]));

        $this->handler->__invoke($this->command);
    }
}
