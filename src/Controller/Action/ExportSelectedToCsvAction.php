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

namespace Sylius\WishlistPlugin\Controller\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\WishlistPlugin\Command\Wishlist\ExportWishlistToCsv;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItem;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ExportSelectedToCsvAction
{
    public function __construct(
        private WishlistRepositoryInterface $wishlistRepository,
        private CartContextInterface $cartContext,
        private AddToCartCommandFactoryInterface $addToCartCommandFactory,
        private CartItemFactoryInterface $cartItemFactory,
        private OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(string $wishlistId, Request $request, SessionInterface $session): BinaryFileResponse|RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find((int) $wishlistId);
        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));
            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        $selectionJson = (string) $request->request->get('selection', '[]');
        $indices = $this->decodeSelection($selectionJson);
        $wishlistName = trim((string) $request->request->get('wishlistName', 'wishlist'));

        $selected = $this->buildSelectedWishlistItems($wishlist->getWishlistProducts(), $indices);
        if ($selected->isEmpty()) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));
            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $wishlistId,
            ]));
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'wishlist_csv_') ?: (sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('wishlist_csv_', true));
        $file = new \SplFileObject($tmpPath, 'w+');

        // Synchronously generate the CSV via message bus and retrieve handler result
        $envelope = $this->messageBus->dispatch(new ExportWishlistToCsv($selected, $file));
        $handled = $envelope->last(HandledStamp::class);
        if ($handled instanceof HandledStamp && $handled->getResult() instanceof \SplFileObject) {
            $file = $handled->getResult();
        }

        $file->rewind();
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, sprintf('%s.csv', $wishlistName !== '' ? $wishlistName : 'wishlist'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /** @return array<int,int> */
    private function decodeSelection(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!\is_array($decoded)) {
            return [];
        }
        return array_values(array_filter($decoded, static fn($v) => \is_int($v) || ctype_digit((string) $v)));
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
            if (null === $wp) { continue; }
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
}
