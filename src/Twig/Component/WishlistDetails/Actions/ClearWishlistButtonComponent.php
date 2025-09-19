<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\WishlistDetails\Actions;

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
final class ClearWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;

    #[LiveProp]
    public int $wishlistId;

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly EntityManagerInterface $wishlistManager,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[LiveAction]
    public function clear(): RedirectResponse
    {
        /** @var ?WishlistInterface $wishlist */
        $wishlist = $this->wishlistRepository->find($this->wishlistId);
        if (null !== $wishlist) {
            $wishlist->clear();
            $this->wishlistManager->flush();
        }

        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.cleared_wishlist'));

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist',
                ['wishlistId' => $this->wishlistId],
            ),
        );
    }
}
