<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\Controller\Action;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\WishlistPlugin\Controller\Action\ListWishlistProductsAction;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Form\Type\WishlistCollectionType;
use Sylius\WishlistPlugin\Processor\WishlistCommandProcessorInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ListWishlistProductsActionTest extends TestCase
{
    private MockObject&CartContextInterface $cartContext;

    private MockObject&FormFactoryInterface $formFactory;

    private MockObject&Environment $twigEnvironment;

    private MockObject&WishlistCommandProcessorInterface $wishlistCommandProcessor;

    private MockObject&WishlistsResolverInterface $wishlistsResolver;

    private MockObject&TranslatorInterface $translator;

    private MockObject&UrlGeneratorInterface $generator;

    private ListWishlistProductsAction $action;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->twigEnvironment = $this->createMock(Environment::class);
        $this->wishlistCommandProcessor = $this->createMock(WishlistCommandProcessorInterface::class);
        $this->wishlistsResolver = $this->createMock(WishlistsResolverInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->generator = $this->createMock(UrlGeneratorInterface::class);
        $this->action = new ListWishlistProductsAction(
            $this->cartContext,
            $this->formFactory,
            $this->twigEnvironment,
            $this->wishlistCommandProcessor,
            $this->wishlistsResolver,
            $this->translator,
            $this->generator,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(ListWishlistProductsAction::class, $this->action);
    }

    public function testShouldListWishlistItems(): void
    {
        $firstWishlist = $this->createMock(WishlistInterface::class);
        $secondWishlist = $this->createMock(WishlistInterface::class);
        $cart = $this->createMock(OrderInterface::class);
        $wishlistProducts = $this->createMock(ArrayCollection::class);
        $commands = $this->createMock(ArrayCollection::class);
        $form = $this->createMock(FormInterface::class);
        $formView = $this->createMock(FormView::class);

        $this->wishlistsResolver->expects($this->once())->method('resolveAndCreate')->willReturn([$firstWishlist, $secondWishlist]);
        $this->cartContext->expects($this->once())->method('getCart')->willReturn($cart);
        $firstWishlist->expects($this->once())->method('getWishlistProducts')->willReturn($wishlistProducts);
        $this->wishlistCommandProcessor->expects($this->once())->method('createWishlistItemsCollection')->with($wishlistProducts)->willReturn($commands);
        $this->formFactory->expects($this->once())->method('create')->with(WishlistCollectionType::class, ['items' => $commands], ['cart' => $cart])->willReturn($form);
        $form->expects($this->once())->method('createView')->willReturn($formView);
        $this->twigEnvironment->expects($this->once())->method('render')->with('@SyliusWishlistPlugin/wishlist_details/index.html.twig', ['wishlist' => $firstWishlist, 'form' => $formView])->willReturn('CONTENT');

        $this->assertInstanceOf(
            Response::class,
            ($this->action)($this->createMock(Request::class)),
        );
    }
}
