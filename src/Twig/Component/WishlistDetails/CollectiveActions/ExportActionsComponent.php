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
use Sylius\WishlistPlugin\Command\Wishlist\ExportSelectedProductsFromWishlistToPdf;
use Sylius\WishlistPlugin\Command\Wishlist\ExportWishlistToCsv;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItem;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ExportActionsComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    #[LiveProp]
    public string $wishlistName = 'wishlist';

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

    #[LiveAction]
    public function exportCsv(): BinaryFileResponse|RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        /** @var Session $session */
        $session = $this->requestStack->getSession();
        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return $this->redirectBack();
        }

        $collection = $this->buildSelectedWishlistItems($wishlist->getWishlistProducts(), $this->decodeSelection());
        if ($collection->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return $this->redirectBack();
        }

        $file = new \SplFileObject(sprintf('%s.csv', $this->wishlistName ?: 'wishlist'), 'w+');
        $csv = new ExportWishlistToCsv($collection, $file);
        $this->messageBus->dispatch($csv);

        $file->rewind();
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());
        $response->headers->set('Content-Type', 'text/csv');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[LiveAction]
    public function exportPdf(): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        /** @var Session $session */
        $session = $this->requestStack->getSession();
        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return $this->redirectBack();
        }

        $collection = $this->buildSelectedWishlistItemsSimple($wishlist->getWishlistProducts(), $this->decodeSelection());
        if ($collection->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));

            return $this->redirectBack();
        }

        $this->messageBus->dispatch(new ExportSelectedProductsFromWishlistToPdf($collection));

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
            $wp = $wishlistProducts->get((int) $index);
            if (null === $wp) {
                continue;
            }
            $wi = new WishlistItem();
            $wi->setWishlistProduct($wp);
            /** @var OrderItemInterface $cartItem */
            $cartItem = $this->cartItemFactory->createForProduct($wp->getProduct());
            $cartItem->setVariant($wp->getVariant());
            $this->orderItemQuantityModifier->modify($cartItem, $wp->getQuantity());
            $wi->setCartItem($this->addToCartCommandFactory->createWithCartAndCartItem($cart, $cartItem));
            $collection->add($wi);
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
            $wp = $wishlistProducts->get((int) $index);
            if (null === $wp) {
                continue;
            }
            $wi = new WishlistItem();
            $wi->setWishlistProduct($wp);
            $collection->add($wi);
        }

        return $collection;
    }
}
