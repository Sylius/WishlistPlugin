<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\Common\AddToWishlist;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Entity\WishlistProductInterface;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Factory\WishlistProductFactoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class AddToWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $productId;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly WishlistProductFactoryInterface $wishlistProductFactory,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly WishlistsResolverInterface $wishlistsResolver,
        private readonly ObjectManager $wishlistManager,
        private readonly ChannelContextInterface $channelContext,
    ) {
    }

    #[LiveAction]
    public function add(): RedirectResponse
    {
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($this->productId);
        if (null === $product) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $wishlists = $this->wishlistsResolver->resolveAndCreate();
        /** @var ?WishlistInterface $wishlist */
        $wishlist = array_shift($wishlists);
        if (null === $wishlist) {
            throw new WishlistNotFoundException($this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_found'));
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
            throw new WishlistNotFoundException($this->translator->trans('sylius_wishlist_plugin.ui.wishlist_for_channel_not_found'));
        }

        /** @var WishlistProductInterface $wishlistProduct */
        $wishlistProduct = $this->wishlistProductFactory->createForWishlistAndProduct($wishlist, $product);
        $wishlist->addWishlistProduct($wishlistProduct);
        $this->wishlistManager->flush();

        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));

        $request = $this->requestStack->getCurrentRequest();
        $referer = $request?->headers->get('referer');
        $refererPath = $referer ? Request::create((string) $referer)->getPathInfo() : '/';

        return new RedirectResponse($refererPath);
    }
}

