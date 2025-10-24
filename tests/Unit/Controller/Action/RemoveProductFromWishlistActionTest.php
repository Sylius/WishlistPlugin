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

namespace Tests\Sylius\WishlistPlugin\Unit\Controller\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Context\WishlistContextInterface;
use Sylius\WishlistPlugin\Controller\Action\RemoveProductFromWishlistAction;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveProductFromWishlistActionTest extends TestCase
{
    private MockObject&WishlistContextInterface $wishlistContext;

    private MockObject&ProductRepositoryInterface $productRepository;

    private MockObject&EntityManagerInterface $wishlistProductManager;

    private MockObject&RequestStack $requestStack;

    private MockObject&TranslatorInterface $translator;

    private MockObject&UrlGeneratorInterface $urlGenerator;

    private MockObject&Request $request;

    private RemoveProductFromWishlistAction $action;

    protected function setUp(): void
    {
        $this->wishlistContext = $this->createMock(WishlistContextInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->wishlistProductManager = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->action = new RemoveProductFromWishlistAction(
            $this->wishlistContext,
            $this->productRepository,
            $this->wishlistProductManager,
            $this->requestStack,
            $this->translator,
            $this->urlGenerator,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(RemoveProductFromWishlistAction::class, $this->action);
    }

    public function testShouldThrow404IfProductWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->request->expects($this->once())->method('get')->with('productId')->willReturn(1);
        $this->productRepository->expects($this->once())->method('find')->willReturn(null);

        ($this->action)($this->request);
    }

    public function testShouldHandleRequestAndRedirectToWishlist(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $channel = $this->createMock(ChannelInterface::class);
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $this->request->expects($this->once())->method('get')->with('productId')->willReturn(1);
        $this->productRepository->expects($this->once())->method('find')->willReturn($product);
        $this->wishlistContext->expects($this->once())->method('getWishlist')->with($this->request)->willReturn($wishlist);
        $wishlist->expects($this->once())->method('getWishlistProducts')->willReturn(new ArrayCollection([$wishlistProduct]));
        $wishlistProduct->expects($this->once())->method('getProduct')->willReturn($product);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.removed_wishlist_item')->willReturn('Product has been removed from your wishlist.');
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_list_products')->willReturn('/wishlist');
        $this->wishlistProductManager->expects($this->once())->method('remove')->with($wishlistProduct);
        $this->wishlistProductManager->expects($this->once())->method('flush');
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($session);
        $session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);
        $flashBag->expects($this->once())->method('add')->with('success', 'Product has been removed from your wishlist.');

        ($this->action)($this->request);
    }
}
