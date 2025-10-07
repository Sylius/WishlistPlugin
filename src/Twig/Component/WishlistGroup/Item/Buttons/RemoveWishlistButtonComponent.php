<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistGroup\Item\Buttons;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class RemoveWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    #[LiveProp]
    public string $wishlistName = '';

    #[LiveProp]
    public bool $showModal = false;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MessageBusInterface $messageBus,
        private readonly WishlistRepositoryInterface $wishlistRepository,
    ) {
    }

    #[LiveAction]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
    }

    #[LiveAction]
    public function remove(): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null !== $wishlist) {
            $this->messageBus->dispatch(new RemoveWishlist($wishlist->getToken()));
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'),
        );
    }
}
