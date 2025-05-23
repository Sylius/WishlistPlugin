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

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddProductVariantToWishlistAction
{
    public function __construct(
        private ProductVariantRepositoryInterface $productVariantRepository,
        private WishlistProductFactoryInterface $wishlistProductFactory,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private WishlistRepositoryInterface $wishlistRepository,
    ) {
    }

    public function __invoke(int $wishlistId, Request $request): Response
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($wishlistId);

        if (null === $wishlist) {
            throw new ResourceNotFoundException();
        }

        foreach ((array) $request->get('variantId') as $variantId) {
            /** @var ProductVariantInterface|null $variant */
            $variant = $this->productVariantRepository->find($variantId);

            if (null === $variant) {
                throw new NotFoundHttpException();
            }

            /** @var WishlistProductInterface $wishlistProduct */
            $wishlistProduct = $this->wishlistProductFactory->createForWishlistAndVariant($wishlist, $variant);

            $this->addProductToWishlist($wishlist, $variant, $wishlistProduct);
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $wishlistId,
            ]),
        );
    }

    private function addProductToWishlist(
        WishlistInterface $wishlist,
        ProductVariantInterface $variant,
        WishlistProductInterface $wishlistProduct,
    ): void {
        /** @var Session $session */
        $session = $this->requestStack->getSession();

        $flashBag = $session->getFlashBag();

        if ($wishlist->hasProductVariant($variant)) {
            $flashBag->add(
                'error',
                $this->translator->trans(
                    'sylius_wishlist_plugin.ui.wishlist_has_product_variant',
                    ['%productName%' => $wishlistProduct->getProduct()->getName()],
                ),
            );

            return;
        }

        $wishlist->addWishlistProduct($wishlistProduct);
        $this->wishlistRepository->add($wishlist);
        $flashBag->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));
    }
}
