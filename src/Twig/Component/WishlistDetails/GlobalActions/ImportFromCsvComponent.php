<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\GlobalActions;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Command\Wishlist\ImportWishlistFromCsv;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ImportFromCsvComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public bool $showModal = false;

    #[LiveProp(writable: true)]
    public ?int $selectedWishlistId = null;

    /** @var array<int, array{id:int,name:string}> */
    #[LiveProp]
    public array $wishlists = [];

    public function __construct(
        private readonly WishlistsResolverInterface $wishlistsResolver,
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
        if ($this->wishlists === []) {
            $resolved = $this->wishlistsResolver->resolveAndCreate();
            foreach ($resolved as $w) {
                $this->wishlists[] = ['id' => (int) $w->getId(), 'name' => (string) $w->getName()];
            }
        }
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedWishlistId = null;
    }

    #[LiveAction]
    public function import(): RedirectResponse
    {
        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || null === $this->selectedWishlistId) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));

            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        $file = $request->files->get('wishlist_file');
        if (null === $file) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.csv_file_contains_incorrect_products'));

            return $this->redirectBack();
        }

        $this->commandBus->dispatch(new ImportWishlistFromCsv($file->getFileInfo(), $request, $this->selectedWishlistId));

        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.changes_saved'));
        $this->showModal = false;

        return $this->redirectBack();
    }

    private function redirectBack(): RedirectResponse
    {
        $id = $this->selectedWishlistId ?? ($this->wishlists[0]['id'] ?? null);
        if ($id === null) {
            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $id,
            ]),
        );
    }
}
