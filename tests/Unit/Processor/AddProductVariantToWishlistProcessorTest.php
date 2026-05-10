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

namespace Tests\Sylius\WishlistPlugin\Unit\Processor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\User\Model\UserInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Processor\AddProductVariantToWishlistProcessor;
use Sylius\WishlistPlugin\Processor\AddProductVariantToWishlistProcessorInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Sylius\WishlistPlugin\Twig\WishlistExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddProductVariantToWishlistProcessorTest extends TestCase
{
    private MockObject&Security $security;

    private MockObject&WishlistExtension $wishlistExtension;

    private MockObject&ChannelContextInterface $channelContext;

    private MockObject&WishlistProductFactoryInterface $wishlistProductFactory;

    private MockObject&RequestStack $requestStack;

    private MockObject&TranslatorInterface $translator;

    private MockObject&UrlGeneratorInterface $urlGenerator;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&WishlistsResolverInterface $wishlistsResolver;

    private MockObject&UserInterface $user;

    private MockObject&WishlistInterface $firstWishlist;

    private MockObject&WishlistInterface $secondWishlist;

    private MockObject&ProductVariantInterface $productVariant;

    private MockObject&WishlistProductInterface $wishlistProduct;

    private MockObject&Session $session;

    private MockObject&FlashBagInterface $flashBag;

    private AddProductVariantToWishlistProcessor $processor;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->wishlistExtension = $this->createMock(WishlistExtension::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->wishlistProductFactory = $this->createMock(WishlistProductFactoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->wishlistsResolver = $this->createMock(WishlistsResolverInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->firstWishlist = $this->createMock(WishlistInterface::class);
        $this->secondWishlist = $this->createMock(WishlistInterface::class);
        $this->productVariant = $this->createMock(ProductVariantInterface::class);
        $this->wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->processor = new AddProductVariantToWishlistProcessor(
            $this->security,
            $this->wishlistExtension,
            $this->channelContext,
            $this->wishlistProductFactory,
            $this->requestStack,
            $this->translator,
            $this->urlGenerator,
            $this->wishlistRepository,
            $this->wishlistsResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(AddProductVariantToWishlistProcessor::class, $this->processor);
    }

    public function testShouldImplementAddProductVariantToWishlistProcessorInterface(): void
    {
        $this->assertInstanceOf(AddProductVariantToWishlistProcessorInterface::class, $this->processor);
    }

    public function testShouldThrowResourceNotFoundExceptionWhenWishlistIsNotFoundForSpecificId(): void
    {
        $wishlistIdToFind = 999;
        $this->expectException(ResourceNotFoundException::class);
        $this->security->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->wishlistExtension->expects($this->once())->method('findAllByShopUserAndToken')->with($this->user)->willReturn([$this->firstWishlist, $this->secondWishlist]);
        $this->wishlistRepository->expects($this->once())->method('find')->with($wishlistIdToFind)->willReturn(null);

        $this->processor->process($this->productVariant, $wishlistIdToFind);
    }

    public function testShouldThrowResourceNotFoundExceptionWhenResolveAndCreateReturnsEmpty(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->security->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->wishlistExtension->expects($this->once())->method('findAllByShopUserAndToken')->with($this->user)->willReturn([]);
        $this->wishlistsResolver->expects($this->once())->method('resolveAndCreate')->willReturn([]);

        $this->processor->process($this->productVariant);
    }

    public function testShouldCreateWishlistAndAddProductForGuestWithNoExistingWishlist(): void
    {
        $wishlistId = 456;
        $channel = $this->createMock(ChannelInterface::class);
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn($wishlistId);
        $this->security->expects($this->once())->method('getUser')->willReturn(null);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($channel);
        $this->wishlistExtension->expects($this->once())->method('findAllByAnonymousAndChannel')->with($channel)->willReturn([]);
        $this->wishlistsResolver->expects($this->once())->method('resolveAndCreate')->willReturn([$this->firstWishlist]);
        $this->firstWishlist->expects($this->once())->method('hasProductVariant')->with($this->productVariant)->willReturn(false);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->firstWishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($this->flashBag);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.added_wishlist_item')->willReturn('Product added.');
        $this->flashBag->expects($this->once())->method('add')->with('success', 'Product added.');
        $this->firstWishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => $wishlistId])->willReturn('/wishlist/' . $wishlistId);

        $response = $this->processor->process($this->productVariant);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/wishlist/' . $wishlistId, $response->getTargetUrl());
    }

    public function testShouldAddProductToTheSingleWishlistForLoggedInUser(): void
    {
        $wishlistId = 123;
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn($wishlistId);
        $this->security->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->wishlistExtension->expects($this->once())->method('findAllByShopUserAndToken')->with($this->user)->willReturn([$this->firstWishlist]);
        $this->firstWishlist->expects($this->once())->method('hasProductVariant')->with($this->productVariant)->willReturn(false);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->firstWishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($this->flashBag);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.added_wishlist_item')->willReturn('Product added.');
        $this->flashBag->expects($this->once())->method('add')->with('success', 'Product added.');
        $this->firstWishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => $wishlistId])->willReturn('/wishlist/' . $wishlistId);

        $response = $this->processor->process($this->productVariant);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/wishlist/' . $wishlistId,
            $response->getTargetUrl(),
        );
    }

    public function testShouldAddProductToSpecificWishlistForLoggedInUserWithMultipleWishlists(): void
    {
        $wishlistId = 789;
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn($wishlistId);
        $this->security->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->wishlistExtension->expects($this->once())->method('findAllByShopUserAndToken')->with($this->user)->willReturn([$this->secondWishlist, $this->firstWishlist]);
        $this->wishlistRepository->expects($this->once())->method('find')->with($wishlistId)->willReturn($this->firstWishlist);
        $this->firstWishlist->expects($this->once())->method('hasProductVariant')->with($this->productVariant)->willReturn(false);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->firstWishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($this->flashBag);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.added_wishlist_item')->willReturn('Product added to specific list.');
        $this->flashBag->expects($this->once())->method('add')->with('success', 'Product added to specific list.');
        $this->firstWishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => $wishlistId])->willReturn('/wishlist/' . $wishlistId);

        $response = $this->processor->process($this->productVariant, $wishlistId);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/wishlist/' . $wishlistId,
            $response->getTargetUrl(),
        );
    }

    public function testShouldAddProductToTheSingleWishlistForAnonymousUser(): void
    {
        $wishlistId = 456;
        $channel = $this->createMock(ChannelInterface::class);
        $this->firstWishlist->expects($this->once())->method('getId')->willReturn($wishlistId);
        $this->security->expects($this->once())->method('getUser')->willReturn(null);
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($channel);
        $this->wishlistExtension->expects($this->once())->method('findAllByAnonymousAndChannel')->with($channel)->willReturn([$this->firstWishlist]);
        $this->firstWishlist->expects($this->once())->method('hasProductVariant')->with($this->productVariant)->willReturn(false);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->firstWishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($this->flashBag);
        $this->translator->expects($this->once())->method('trans')->with('sylius_wishlist_plugin.ui.added_wishlist_item')->willReturn('Product added (anonymous).');
        $this->flashBag->expects($this->once())->method('add')->with('success', 'Product added (anonymous).');
        $this->firstWishlist->expects($this->once())->method('addWishlistProduct')->with($this->wishlistProduct);
        $this->wishlistRepository->expects($this->once())->method('add')->with($this->firstWishlist);
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => $wishlistId])->willReturn('/wishlist/anon/' . $wishlistId);

        $response = $this->processor->process($this->productVariant);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/wishlist/anon/' . $wishlistId,
            $response->getTargetUrl(),
        );
    }

    public function testShouldAddFlashErrorIfProductVariantIsAlreadyInWishlist(): void
    {
        $wishlistId = 123;
        $product = $this->createMock(ProductInterface::class);
        $productName = 'Awesome T-Shirt';
        $translatedMessage = sprintf('Product "%s" is already in wishlist.', $productName);

        $this->firstWishlist->expects($this->once())->method('getId')->willReturn($wishlistId);
        $this->wishlistProduct->expects($this->once())->method('getProduct')->willReturn($product);
        $product->expects($this->once())->method('getName')->willReturn($productName);
        $this->security->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->wishlistExtension->expects($this->once())->method('findAllByShopUserAndToken')->with($this->user)->willReturn([$this->firstWishlist]);
        $this->firstWishlist->expects($this->once())->method('hasProductVariant')->with($this->productVariant)->willReturn(true);
        $this->wishlistProductFactory->expects($this->once())->method('createForWishlistAndVariant')->with($this->firstWishlist, $this->productVariant)->willReturn($this->wishlistProduct);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($this->flashBag);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'sylius_wishlist_plugin.ui.wishlist_has_product_variant',
                ['%productName%' => $productName],
            )
            ->willReturn($translatedMessage);
        $this->flashBag->expects($this->once())->method('add')->with('error', $translatedMessage);
        $this->firstWishlist->expects($this->never())->method('addWishlistProduct');
        $this->urlGenerator->expects($this->once())->method('generate')->with('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', ['wishlistId' => $wishlistId])->willReturn('/wishlist/' . $wishlistId);

        $response = $this->processor->process($this->productVariant);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/wishlist/' . $wishlistId,
            $response->getTargetUrl(),
        );
    }
}
