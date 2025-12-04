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

use Doctrine\Persistence\ObjectManager;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddProductVariantToWishlistAction
{
    public function __construct(
        private ProductVariantRepositoryInterface $productVariantRepository,
        private WishlistProductFactoryInterface $wishlistProductFactory,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private WishlistsResolverInterface $wishlistsResolver,
        private ObjectManager $wishlistManager,
        private RouterInterface $router,
        private WishlistRepositoryInterface $wishlistRepository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $wishlist = $this->resolveWishlist($request);

        foreach ((array) $request->get('variantId') as $variantId) {
            /** @var ProductVariantInterface|null $variant */
            $variant = $this->productVariantRepository->find($variantId);

            if (null === $variant) {
                throw new NotFoundHttpException();
            }

            /** @var WishlistProductInterface $wishlistProduct */
            $wishlistProduct = $this->wishlistProductFactory->createForWishlistAndVariant($wishlist, $variant);
            $wishlist->addWishlistProduct($wishlistProduct);
        }

        $this->wishlistManager->flush();
        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));

        return new RedirectResponse(
            $this->router->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $wishlist->getId(),
            ]),
        );
    }

    private function resolveWishlist(Request $request): WishlistInterface
    {
        $wishlistId = $request->get('wishListId');
        if (null !== $wishlistId) {
            $wishlist = $this->wishlistRepository->find($wishlistId);

            if ($wishlist instanceof WishlistInterface) {
                return $wishlist;
            }
        }

        $wishlists = $this->wishlistsResolver->resolveAndCreate();
        $wishlist = array_shift($wishlists);

        if ($wishlist instanceof WishlistInterface) {
            return $wishlist;
        }

        throw new WishlistNotFoundException(
            $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_found'),
        );
    }
}
