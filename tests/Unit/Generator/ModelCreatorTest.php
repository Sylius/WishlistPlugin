<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Generator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\OrderBundle\Controller\AddToCartCommandInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItemInterface;
use Sylius\WishlistPlugin\Generator\ModelCreator;
use Sylius\WishlistPlugin\Generator\ModelCreatorInterface;
use Sylius\WishlistPlugin\Model\Factory\VariantPdfModelFactoryInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;
use Sylius\WishlistPlugin\Resolver\VariantImageToDataUriResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ModelCreatorTest extends TestCase
{
    private MockObject&VariantImageToDataUriResolverInterface $variantImageToDataUriResolver;

    private MockObject&VariantPdfModelFactoryInterface $variantPdfModelFactory;

    private MockObject&RequestStack $requestStack;

    private ModelCreator $creator;

    protected function setUp(): void
    {
        $this->variantImageToDataUriResolver = $this->createMock(VariantImageToDataUriResolverInterface::class);
        $this->variantPdfModelFactory = $this->createMock(VariantPdfModelFactoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->creator = new ModelCreator(
            $this->variantImageToDataUriResolver,
            $this->variantPdfModelFactory,
            $this->requestStack,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(ModelCreator::class, $this->creator);
    }

    public function testShouldImplementModelCreatorInterface(): void
    {
        $this->assertInstanceOf(ModelCreatorInterface::class, $this->creator);
    }

    public function testShouldCreatePdfModel(): void
    {
        $wishlistItem = $this->createMock(WishlistItemInterface::class);
        $orderItem = $this->createMock(OrderItemInterface::class);
        $addToCartCommand = $this->createMock(AddToCartCommandInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);
        $request = $this->createMock(Request::class);
        $pdfModel = $this->createMock(VariantPdfModelInterface::class);

        $wishlistItem->expects($this->once())->method('getCartItem')->willReturn($addToCartCommand);
        $addToCartCommand->expects($this->once())->method('getCartItem')->willReturn($orderItem);
        $orderItem->expects($this->once())->method('getVariant')->willReturn($variant);
        $orderItem->expects($this->once())->method('getQuantity')->willReturn(1);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        $request->expects($this->once())->method('getSchemeAndHttpHost')->willReturn('host');
        $this->variantImageToDataUriResolver->expects($this->once())->method('resolve')->with($variant, 'host')->willReturn('url');
        $variant->expects($this->once())->method('getCode')->willReturn('code');
        $this->variantPdfModelFactory->expects($this->once())->method('createWithVariantAndImagePath')->with($variant, 'url', 1, 'code')->willReturn($pdfModel);

        $this->assertSame(
            $pdfModel,
            $this->creator->createWishlistItemToPdf($wishlistItem),
        );
    }
}
