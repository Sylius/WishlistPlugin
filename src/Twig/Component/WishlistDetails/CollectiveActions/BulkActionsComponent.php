<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\CollectiveActions;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Command\Wishlist\AddSelectedProductsToCart;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveSelectedProductsFromWishlist;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItem;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
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
final class BulkActionsComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    #[LiveProp(writable: true)]
    public string $selection = '[]';

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly CartContextInterface $cartContext,
        private readonly AddToCartCommandFactoryInterface $addToCartCommandFactory,
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** @return array<int,int> */
    private function decodeSelection(): array
    {
        $decoded = json_decode($this->selection, true);
        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($v) => \is_int($v) || ctype_digit((string) $v)));
    }

    /** @return array<int,int> */
    private function decodeSelectionString(?string $selection): array
    {
        if (null === $selection) {
            return [];
        }
        $decoded = json_decode($selection, true);
        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($v) => \is_int($v) || ctype_digit((string) $v)));
    }

    #[LiveAction]
    public function addSelected(?string $selection = null): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        /** @var Session $session */
        $session = $this->requestStack->getSession();

        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        $indices = $selection !== null ? $this->decodeSelectionString($selection) : $this->decodeSelection();
        $selected = $this->buildSelectedWishlistItems($wishlist->getWishlistProducts(), $indices);
        if ($selected->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return $this->redirectBack();
        }

        try {
            $this->messageBus->dispatch(new AddSelectedProductsToCart($selected));
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_to_cart'));
        } catch (HandlerFailedException $e) {
            $session->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->redirectBack();
    }

    #[LiveAction]
    public function removeSelected(?string $selection = null): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        /** @var Session $session */
        $session = $this->requestStack->getSession();

        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        $indices = $selection !== null ? $this->decodeSelectionString($selection) : $this->decodeSelection();
        $selected = $this->buildSelectedWishlistItemsSimple($wishlist->getWishlistProducts(), $indices);
        if ($selected->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return $this->redirectBack();
        }

        try {
            $this->messageBus->dispatch(new RemoveSelectedProductsFromWishlist($selected));
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.removed_selected_wishlist_items'));
        } catch (HandlerFailedException $e) {
            $session->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->redirectBack();
    }

    private function redirectBack(): RedirectResponse
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $this->wishlistId,
            ]),
        );
    }

    /**
     * @param Collection<int,mixed> $wishlistProducts
     * @param array<int,int> $indices
     */
    private function buildSelectedWishlistItems(Collection $wishlistProducts, array $indices): ArrayCollection
    {
        $cart = $this->cartContext->getCart();
        $collection = new ArrayCollection();

        foreach ($indices as $index) {
            $wishlistProduct = $wishlistProducts->get((int) $index);
            if (null === $wishlistProduct) {
                continue;
            }
            $wishlistItem = new WishlistItem();
            $wishlistItem->setWishlistProduct($wishlistProduct);

            /** @var OrderItemInterface $cartItem */
            $cartItem = $this->cartItemFactory->createForProduct($wishlistProduct->getProduct());
            $cartItem->setVariant($wishlistProduct->getVariant());
            $this->orderItemQuantityModifier->modify($cartItem, $wishlistProduct->getQuantity());

            $wishlistItem->setCartItem(
                $this->addToCartCommandFactory->createWithCartAndCartItem($cart, $cartItem),
            );

            $collection->add($wishlistItem);
        }

        return $collection;
    }

    /**
     * @param Collection<int,mixed> $wishlistProducts
     * @param array<int,int> $indices
     */
    private function buildSelectedWishlistItemsSimple(Collection $wishlistProducts, array $indices): ArrayCollection
    {
        $collection = new ArrayCollection();
        foreach ($indices as $index) {
            $wishlistProduct = $wishlistProducts->get((int) $index);
            if (null === $wishlistProduct) {
                continue;
            }
            $wishlistItem = new WishlistItem();
            $wishlistItem->setWishlistProduct($wishlistProduct);
            $collection->add($wishlistItem);
        }

        return $collection;
    }
}
