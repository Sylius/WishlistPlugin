<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddSelectedProductsToCart;
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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class WishlistItemsComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    /** @var array<int, bool> */
    #[LiveProp(writable: true)]
    public array $selection = [];

    #[LiveProp]
    public int $wishlistId;

    private bool $allSelected = false;

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

    #[LiveAction]
    public function addSelected(): RedirectResponse
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

        $selected = $this->buildSelectedWishlistItems($wishlist->getWishlistProducts());

        if ($selected->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return new RedirectResponse(
                $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                    'wishlistId' => $this->wishlistId,
                ]),
            );
        }

        try {
            $this->messageBus->dispatch(new AddSelectedProductsToCart($selected));
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

    #[LiveAction]
    public function removeSelected(): RedirectResponse
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

        $selected = $this->buildSelectedWishlistItemsSimple($wishlist->getWishlistProducts());

        if ($selected->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return new RedirectResponse(
                $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                    'wishlistId' => $this->wishlistId,
                ]),
            );
        }

        try {
            $this->messageBus->dispatch(new \Sylius\WishlistPlugin\Command\Wishlist\RemoveSelectedProductsFromWishlist($selected));
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.items_removed'));
        } catch (HandlerFailedException $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $this->wishlistId,
            ]),
        );
    }

    #[LiveAction]
    public function toggleAll(): void
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null === $wishlist) {
            return;
        }

        $count = $wishlist->getWishlistProducts()->count();
        $this->allSelected = !$this->allSelected;

        $new = [];
        for ($i = 0; $i < $count; ++$i) {
            $new[$i] = $this->allSelected;
        }
        $this->selection = $new;
    }

    /**
     * @param Collection<int, mixed> $wishlistProducts
     */
    private function buildSelectedWishlistItems(Collection $wishlistProducts): ArrayCollection
    {
        $cart = $this->cartContext->getCart();

        $collection = new ArrayCollection();

        foreach ($this->selection as $index => $checked) {
            if (true !== $checked) {
                continue;
            }

            $wishlistProduct = $wishlistProducts->get($index);
            if (null === $wishlistProduct) {
                continue;
            }

            $wishlistItem = new WishlistItem();
            $wishlistItem->setWishlistProduct($wishlistProduct);

            // mirror AddProductsToCartType construction
            $cartItem = $this->createCartItem($wishlistProduct->getProduct(), $wishlistProduct->getVariant(), $wishlistProduct->getQuantity());
            $wishlistItem->setCartItem(
                $this->addToCartCommandFactory->createWithCartAndCartItem($cart, $cartItem),
            );

            $collection->add($wishlistItem);
        }

        return $collection;
    }

    private function createCartItem($product, $variant, int $quantity): OrderItemInterface
    {
        /** @var OrderItemInterface $cartItem */
        $cartItem = $this->cartItemFactory->createForProduct($product);
        $cartItem->setVariant($variant);
        $this->orderItemQuantityModifier->modify($cartItem, $quantity);

        return $cartItem;
    }

    /**
     * @param Collection<int, mixed> $wishlistProducts
     */
    private function buildSelectedWishlistItemsSimple(Collection $wishlistProducts): ArrayCollection
    {
        $collection = new ArrayCollection();

        foreach ($this->selection as $index => $checked) {
            if (true !== $checked) {
                continue;
            }

            $wishlistProduct = $wishlistProducts->get($index);
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
