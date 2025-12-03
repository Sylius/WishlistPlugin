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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddProductVariantToWishlistAction
{
    public function __construct(
        private ProductVariantRepositoryInterface $productVariantRepository,
        private WishlistProductFactoryInterface   $wishlistProductFactory,
        private RequestStack                      $requestStack,
        private TranslatorInterface               $translator,
        private WishlistsResolverInterface        $wishlistsResolver,
        private ObjectManager $wishlistManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $variant = $this->productVariantRepository->find($request->get('variantId'));

        if (null === $variant) {
            throw new NotFoundHttpException(sprintf('Product variantwith code %s not found.', $request->get('variantId')));
        }
        $wishlists = $this->wishlistsResolver->resolveAndCreate();
        $wishlist = array_shift($wishlists);

        if (null === $wishlist) {
            throw new WishlistNotFoundException(
                $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_found'),
            );
        }
            /** @var WishlistProductInterface $wishlistProduct */
        $wishlistProduct = $this->wishlistProductFactory->createForWishlistAndVariant($wishlist, $variant);
        $wishlist->addWishlistProduct($wishlistProduct);

        $this->wishlistManager->flush();
        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));

        $referer = $request->headers->get('referer');
        $refererPathInfo = Request::create((string) $referer)->getPathInfo();

        return new RedirectResponse($refererPathInfo);
    }
}
