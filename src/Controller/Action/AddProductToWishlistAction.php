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
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Resolver\RefererPathResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddProductToWishlistAction
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private WishlistProductFactoryInterface $wishlistProductFactory,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private WishlistsResolverInterface $wishlistsResolver,
        private ObjectManager $wishlistManager,
        private ChannelContextInterface $channelContext,
        private ?RefererPathResolverInterface $refererPathResolver = null,
    ) {
        if (null === $this->refererPathResolver) {
            trigger_deprecation(
                'sylius/wishlist-plugin',
                '1.3',
                'Not passing an instance of %s to %s is deprecated and it will be required in 2.0.',
                RefererPathResolverInterface::class,
                self::class,
            );
        }
    }

    public function __invoke(Request $request): Response
    {
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($request->get('productId'));

        if (null === $product) {
            throw new NotFoundHttpException();
        }

        $wishlists = $this->wishlistsResolver->resolveAndCreate();

        /** @var ?WishlistInterface $wishlist */
        $wishlist = array_shift($wishlists);

        if (null === $wishlist) {
            throw new WishlistNotFoundException(
                $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_found'),
            );
        }

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            $channel = null;
        }

        /** @var ?ChannelInterface $wishlistChannel */
        $wishlistChannel = $wishlist->getChannel();

        if (null === $wishlistChannel) {
            throw new ChannelNotFoundException();
        }

        if (null !== $channel && $wishlistChannel->getId() !== $channel->getId()) {
            throw new WishlistNotFoundException(
                $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_for_channel_not_found'),
            );
        }

        /** @var WishlistProductInterface $wishlistProduct */
        $wishlistProduct = $this->wishlistProductFactory->createForWishlistAndProduct($wishlist, $product);

        $wishlist->addWishlistProduct($wishlistProduct);

        $this->wishlistManager->flush();

        /** @var Session $session */
        $session = $this->requestStack->getSession();

        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));

        if (null === $this->refererPathResolver) {
            return new RedirectResponse(Request::create((string) $request->headers->get('referer'))->getPathInfo());
        }

        return new RedirectResponse($this->refererPathResolver->resolve($request));
    }
}
