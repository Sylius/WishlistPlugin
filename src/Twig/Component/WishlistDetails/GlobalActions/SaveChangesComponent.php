<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\GlobalActions;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SaveChangesComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    // JSON-encoded map { index: quantity }
    #[LiveProp(writable: true)]
    public string $quantities = '{}';

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly EntityManagerInterface $wishlistProductManager,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        /** @var Session $session */
        $session = $this->requestStack->getSession();

        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null === $wishlist) {
            $session->getFlashBag()->add('error', $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_not_exists'));
            return new RedirectResponse($this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_list_wishlists'));
        }

        $map = json_decode($this->quantities, true);
        if (!\is_array($map)) {
            $map = [];
        }

        foreach ($map as $index => $qty) {
            $wishlistProduct = $wishlist->getWishlistProducts()->get((int) $index);
            if (null === $wishlistProduct) {
                continue;
            }
            $wishlistProduct->setQuantity(max(0, (int) $qty));
            $this->wishlistProductManager->persist($wishlistProduct);
        }
        $this->wishlistProductManager->flush();

        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.changes_saved'));

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => $this->wishlistId,
            ]),
        );
    }
}

