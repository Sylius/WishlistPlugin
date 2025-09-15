<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistGroup\Item;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveWishlist;
use Sylius\WishlistPlugin\Command\Wishlist\UpdateWishlistName;
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
final class WishlistItemComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    #[LiveProp]
    public string $name = '';

    #[LiveProp]
    public bool $isLast = false;

    #[LiveProp]
    public bool $showEditModal = false;

    #[LiveProp]
    public bool $showRemoveModal = false;

    #[LiveProp(writable: true)]
    public string $editName = '';

    #[LiveProp]
    public ?string $localMessage = null;
    #[LiveProp]
    public string $localMessageType = 'success';

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function openEdit(): void
    {
        $this->showEditModal = true;
        $this->editName = $this->name;
    }

    #[LiveAction]
    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editName = $this->name;
    }

    #[LiveAction]
    public function saveName(): void
    {
        $newName = trim($this->editName);
        if ($newName === '') {
            return;
        }

        /** @var Session $session */
        $session = $this->requestStack->getSession();

        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null === $wishlist) {
            return;
        }

        try {
            $this->messageBus->dispatch(new UpdateWishlistName($newName, $wishlist));
            $this->name = $newName;
            $this->showEditModal = false;
            $this->localMessageType = 'success';
            $this->localMessage = $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_changed');
            $session->getFlashBag()->add('success', $this->localMessage);
        } catch (HandlerFailedException) {
            $this->localMessageType = 'error';
            $this->localMessage = $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_already_exists');
            $session->getFlashBag()->add('error', $this->localMessage);
        }
    }

    #[LiveAction]
    public function openRemove(): void
    {
        $this->showRemoveModal = true;
    }

    #[LiveAction]
    public function closeRemove(): void
    {
        $this->showRemoveModal = false;
    }

    #[LiveAction]
    public function remove(): RedirectResponse
    {
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null !== $wishlist) {
            $this->messageBus->dispatch(new RemoveWishlist($wishlist->getToken()));
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'),
        );
    }
}
