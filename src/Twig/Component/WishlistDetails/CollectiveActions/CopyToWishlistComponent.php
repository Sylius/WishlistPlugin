<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\CollectiveActions;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Sylius\WishlistPlugin\Command\Wishlist\CopySelectedProductsToOtherWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CopyToWishlistComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;
    /** @var array<int, array{name: string, id: int}> */
    #[LiveProp]
    public array $wishlists = [];

    #[LiveProp]
    public bool $showModal = false;
    #[LiveProp(writable: true)]
    public ?int $selectedId = null;
    /** JSON string with preselected variant ids from main list */
    #[LiveProp(writable: true)]
    public string $preselected = '[]';

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly WishlistsResolverInterface $wishlistsResolver,
    ) {
    }

    #[LiveAction]
    public function openModal(?string $selection = null): void
    {
        $this->showModal = true;
        if ($this->wishlists === []) {
            $resolved = $this->wishlistsResolver->resolveAndCreate();
            foreach ($resolved as $w) {
                $this->wishlists[] = ['id' => (int) $w->getId(), 'name' => (string) $w->getName()];
            }
        }
        if (null !== $selection) {
            $this->preselected = $selection;
        }
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedId = null;
    }

    #[LiveAction]
    public function copy(?string $selection = null): RedirectResponse
    {
        /** @var Session $session */
        $session = $this->requestStack->getSession();

        if (null === $this->selectedId) {
            // no destination selected, keep modal open
            return $this->redirectBack();
        }

        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null === $wishlist) {
            return $this->redirectBack();
        }

        // Build selected variant ids
        $variantIds = [];
        if (null !== $selection) {
            $decoded = json_decode($selection, true);
            if (\is_array($decoded)) {
                $variantIds = array_map('intval', $decoded);
            }
        } else {
            $decoded = json_decode($this->preselected, true);
            if (\is_array($decoded)) {
                $variantIds = array_map('intval', $decoded);
            }
        }

        if (empty($variantIds)) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.select_products'));
            return $this->redirectBack();
        }

        $collection = new ArrayCollection();
        foreach ($wishlist->getWishlistProducts() as $wp) {
            if (null === $wp->getVariant()) { continue; }
            $vid = (int) $wp->getVariant()->getId();
            if (in_array($vid, $variantIds, true)) {
                $collection->add(['variant' => $vid]);
            }
        }

        $this->commandBus->dispatch(new CopySelectedProductsToOtherWishlist($collection, $this->selectedId));

        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.copied_selected_wishlist_items'));

        $this->showModal = false;

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
}
