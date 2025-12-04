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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Controller\Action\AddProductVariantToWishlistAction;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddProductVariantToWishlistActionTest extends TestCase
{
    private MockObject&ProductVariantRepositoryInterface $productVariantRepository;

    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&RequestStack $requestStack;

    private MockObject&TranslatorInterface $translator;

    private MockObject&UrlGeneratorInterface $urlGenerator;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&Request $request;

    private MockObject&WishlistInterface $wishlist;

    private AddProductVariantToWishlistAction $action;

    protected function setUp(): void
    {
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->action = new AddProductVariantToWishlistAction(
            $this->productVariantRepository,
            $this->wishlistProductFactory,
            $this->requestStack,
            $this->translator,
            $this->urlGenerator,
            $this->wishlistRepository,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductVariantToWishlistAction::class, $this->action);
    }

    public function testShouldThrow404WhenWishlistIsNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->wishlistRepository->expects($this->once())->method('find')->with(1)->willReturn(null);
        $this->request->expects($this->once())->method('get')->with('wishListId')->willReturn(1);
        ($this->action)($this->request);
    }

    public function testShouldThrow404WhenProductIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->wishlistRepository->expects($this->once())->method('find')->with(1)->willReturn($this->wishlist);
        $this->request->expects($this->once())->method('get')->with('variantId')->willReturn(1);
        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn(null);

        ($this->action)($this->request);
    }

    public function testShouldHandleTheRequestAndPersistNewWishlistForLoggedShopUser(): void
    {
        $productVariant = $this->createMock(ProductVariantInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $this->wishlistRepository->expects($this->once())->method('find')->with(1)->willReturn($this->wishlist);
        $this->request->expects($this->once())->method('get')->with('variantId')->willReturn(1);
        $this->productVariantRepository->expects($this->once())->method('find')->with(1)->willReturn($productVariant);
        $this->wishlist->expects($this->once())->method('hasProductVariant')->with($productVariant)->willReturn(false);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->wishlist, $productVariant)->willReturn($wishlistProduct);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.added_wishlist_item')->willReturn('Product has been added to your wishlist.');
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => 1])->willReturn('/wishlist/1');
        $this->wishlist->expects($this->once())->method('addWishlistProduct')->with($wishlistProduct);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->wishlist);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($session);
        $session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);
        $flashBag->expects($this->once())->method('add')->with('success', 'Product has been added to your wishlist.');

        $this->assertInstanceOf(
            RedirectResponse::class,
            ($this->action)($this->request),
        );
    }
}
