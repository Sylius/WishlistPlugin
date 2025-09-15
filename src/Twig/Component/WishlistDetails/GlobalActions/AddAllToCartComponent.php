<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\GlobalActions;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductsToCart;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Processor\WishlistCommandProcessorInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class AddAllToCartComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly WishlistCommandProcessorInterface $wishlistCommandProcessor,
        private readonly MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[LiveAction]
    public function addAll(): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);

        /** @var Session $session */
        $session = $this->requestStack->getSession();

        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return new RedirectResponse(
                $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'),
            );
        }

        $items = $this->wishlistCommandProcessor->createWishlistItemsCollection($wishlist->getWishlistProducts());

        try {
            $this->messageBus->dispatch(new AddProductsToCart($items));
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_to_cart'));
        } catch (HandlerFailedException $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $this->wishlistId,
            ]),
        );
    }
}

