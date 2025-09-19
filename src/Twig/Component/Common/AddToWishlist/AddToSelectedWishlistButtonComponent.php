<?php

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Twig\Component\Common\AddToWishlist;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\Trait\ProductLivePropTrait;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductToSelectedWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\ProductNotFoundException;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
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
final class AddToSelectedWishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use TemplatePropTrait;
    use HookableLiveComponentTrait;
    use ProductLivePropTrait;

    #[LiveProp(hydrateWith: 'hydrateWishlist', dehydrateWith: 'dehydrateWishlist')]
    public ?WishlistInterface $wishlist = null;

    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        ProductRepositoryInterface $productRepository,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MessageBusInterface $commandBus,
    ) {
        $this->initializeProduct($productRepository);
    }

    #[LiveAction]
    public function add(): RedirectResponse
    {
        $wishlist = $this->wishlist;
        if (null === $wishlist) {
            throw new WishlistNotFoundException('Wishlist not found.');
        }

        /** @var ?ProductInterface $product */
        $product = $this->product;
        if (null === $product) {
            throw new ProductNotFoundException('Product not found.');
        }

        $this->commandBus->dispatch(new AddProductToSelectedWishlist($wishlist, $product));

        /** @var Session $session */
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.added_wishlist_item'));

        return new RedirectResponse(
            $this->urlGenerator->generate('sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist', [
                'wishlistId' => (int) $wishlist->getId(),
            ]),
        );
    }

    public function hydrateWishlist(mixed $value): ?WishlistInterface
    {
        /** @var WishlistInterface|null $wishlist */
        $wishlist = $this->wishlistRepository->find($value);

        return $wishlist;
    }

    public function dehydrateWishlist(?WishlistInterface $wishlist): mixed
    {
        return $wishlist?->getId();
    }
}
