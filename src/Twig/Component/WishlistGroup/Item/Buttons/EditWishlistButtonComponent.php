<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistGroup\Item\Buttons;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
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
final class EditWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;
    #[LiveProp]
    public string $currentName = '';
    #[LiveProp(writable: true)]
    public string $name = '';
    #[LiveProp]
    public bool $showModal = false;

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->name = $this->currentName;
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->name = $this->currentName;
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $name = trim($this->name);

        if ($name === '') {
            return null;
        }

        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);

        if (null === $wishlist) {
            return null;
        }

        /** @var Session $session */
        $session = $this->requestStack->getSession();

        try {
            $this->commandBus->dispatch(new UpdateWishlistName($name, $wishlist));
            $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_changed'));
            $this->showModal = false;
            $this->currentName = $name;
            $this->name = $name;
            return null;
        } catch (HandlerFailedException) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_already_exists'));
            return null;
        }
    }
}
