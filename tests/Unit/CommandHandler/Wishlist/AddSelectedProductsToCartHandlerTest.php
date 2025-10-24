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
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddSelectedProductsToCart;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItem;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\AddSelectedProductsToCartHandler;
use Sylius\WishlistPlugin\Exception\InvalidProductQuantityException;

final class AddSelectedProductsToCartHandlerTest extends TestCase
{
    private MockObject&OrderItemQuantityModifierInterface $itemQuantityModifier;

    private MockObject&OrderModifierInterface $orderModifier;

    private MockObject&OrderRepositoryInterface $orderRepository;

    private MockObject&AvailabilityCheckerInterface $availabilityChecker;

    private MockObject&WishlistItem $wishlistProduct;

    private MockObject&OrderInterface $order;

    private MockObject&OrderItemInterface $orderItem;

    private MockObject&AddToCartCommandInterface $addToCartCommand;

    private MockObject&ProductVariantInterface $productVariant;

    private AddSelectedProductsToCart $command;

    private AddSelectedProductsToCartHandler $handler;

    protected function setUp(): void
    {
        $this->itemQuantityModifier = $this->createMock(OrderItemQuantityModifierInterface::class);
        $this->orderModifier = $this->createMock(OrderModifierInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityChecker = $this->createMock(AvailabilityCheckerInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistItem::class);
        $this->order = $this->createMock(OrderInterface::class);
        $this->orderItem = $this->createMock(OrderItemInterface::class);
        $this->addToCartCommand = $this->createMock(AddToCartCommandInterface::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->command = new AddSelectedProductsToCart(
            new ArrayCollection([$this->wishlistProduct]),
        );
        $this->handler = new AddSelectedProductsToCartHandler(
            $this->itemQuantityModifier,
            $this->orderModifier,
            $this->orderRepository,
            $this->availabilityChecker,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddSelectedProductsToCartHandler::class, $this->handler);
    }

    public function testShouldAddSelectedProductsToCart(): void
    {
        $this->wishlistProduct->expects($this->exactly(2))->method('getCartItem')->willReturn($this->addToCartCommand);
        $this->addToCartCommand->expects($this->once())->method('getCart')->willReturn($this->order);
        $this->addToCartCommand->expects($this->exactly(2))->method('getCartItem')->willReturn($this->orderItem);
        $this->orderItem->expects($this->once())->method('getVariant')->willReturn($this->productVariant);
        $this->orderItem->expects($this->exactly(3))->method('getQuantity')->willReturn(1);
        $this->orderModifier->expects($this->once())->method('addToOrder')->with($this->order, $this->orderItem);
        $this->orderRepository->expects($this->once())->method('add')->with($this->order);
        $this->availabilityChecker->expects($this->once())->method('isStockSufficient')->with($this->productVariant, 1)->willReturn(true);

        ($this->handler)($this->command);
    }

    public function testShouldNotAddSelectedProductsToCartIfProductCannotBeProcessedButThrowsException(): void
    {
        $this->expectException(InvalidProductQuantityException::class);
        $this->wishlistProduct->expects($this->once())->method('getCartItem')->willReturn($this->addToCartCommand);
        $this->addToCartCommand->expects($this->once())->method('getCartItem')->willReturn($this->orderItem);
        $this->orderItem->expects($this->once())->method('getVariant')->willReturn($this->productVariant);
        $this->orderItem->expects($this->exactly(2))->method('getQuantity')->willReturn(0);
        $this->availabilityChecker->expects($this->once())->method('isStockSufficient')->with($this->productVariant, 0)->willReturn(true);
        $this->orderModifier->expects($this->never())->method('addToOrder')->with($this->order, $this->orderItem);
        $this->orderRepository->expects($this->never())->method('add')->with($this->order);

        ($this->handler)($this->command);
    }
}
