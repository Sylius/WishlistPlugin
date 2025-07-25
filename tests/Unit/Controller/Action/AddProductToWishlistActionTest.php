<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Controller\Action;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Controller\Action\AddProductToWishlistAction;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddProductToWishlistActionTest extends TestCase
{
    private MockObject&ProductRepositoryInterface $productRepository;

    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&RequestStack $requestStack;

    private MockObject&TranslatorInterface $translator;

    private MockObject&WishlistsResolverInterface $wishlistsResolver;

    private MockObject&ObjectManager $wishlistManager;

    private MockObject&ChannelContextInterface $channelContext;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&Request $request;

    private AddProductToWishlistAction $action;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->wishlistsResolver = $this->createMock(WishlistsResolverInterface::class);
        $this->wishlistManager = $this->createMock(ObjectManager::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->action = new AddProductToWishlistAction(
            $this->productRepository,
            $this->wishlistProductFactory,
            $this->requestStack,
            $this->translator,
            $this->wishlistsResolver,
            $this->wishlistManager,
            $this->channelContext,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductToWishlistAction::class, $this->action);
    }

    public function testShouldThrow404WhenProductIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->request
            ->expects($this->once())
            ->method('get')
            ->willReturn(1);
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->action->__invoke($this->request);
    }

    public function testShouldHandleTheRequestAndPersistNewWishlistForLoggedShopUser(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $firstWishlist = $this->createMock(WishlistInterface::class);
        $secondWishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $channel = $this->createMock(ChannelInterface::class);
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);
        $headers = $this->createMock(HeaderBag::class);
        $this->request->headers = $headers;

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('productId')
            ->willReturn(1);
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);
        $this->wishlistsResolver
            ->expects($this->once())
            ->method('resolveAndCreate')
            ->willReturn([
                $firstWishlist,
                $secondWishlist,
            ]);
        $this->wishlistProductFactory
            ->expects($this->once())
            ->method('createForWishlistAndProduct')
            ->with($firstWishlist, $product)
            ->willReturn($wishlistProduct);
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('sylius_wishlist_plugin.ui.added_wishlist_item')
            ->willReturn('Product has been added to your wishlist.');
        $this->channelContext
            ->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $channel
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $firstWishlist
            ->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $firstWishlist
            ->expects($this->once())
            ->method('addWishlistProduct')
            ->with($wishlistProduct);
        $this->wishlistManager
            ->expects($this->once())
            ->method('flush');
        $this->requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session);
        $session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('success', 'Product has been added to your wishlist.');
        $headers
            ->expects($this->once())
            ->method('get')
            ->with('referer')
            ->willReturn('value');

        $this->assertInstanceOf(
            RedirectResponse::class,
            $this->action->__invoke($this->request),
        );
    }
}
