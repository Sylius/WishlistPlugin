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

namespace Sylius\WishlistPlugin\Twig\Component;

use Sylius\Bundle\ShopBundle\Twig\Component\Product\AddToCartFormComponent;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Sylius\WishlistPlugin\Checker\WishlistProductCheckerInterface;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductToWishlist;
use Sylius\WishlistPlugin\Command\Wishlist\AddProductVariantToWishlist;
use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductFromWishlist;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class WishlistButtonComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;

    #[LiveProp]
    public ?int $productId = null;

    #[LiveProp]
    public ?int $variantId = null;

    #[LiveProp]
    public bool $isInWishlist = false;

    private ?ProductInterface $product = null;

    /** @param ProductRepositoryInterface<ProductInterface> $productRepository */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly WishlistsResolverInterface $wishlistsResolver,
        private readonly WishlistProductCheckerInterface $wishlistProductChecker,
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[PostMount]
    public function postMount(): void
    {
        $product = $this->getProduct();
        if ($product === null) {
            return;
        }

        $this->isInWishlist = $this->wishlistProductChecker->isProductInAnyWishlist(
            $product,
            $this->getWishlists(),
        );
    }

    #[LiveListener(AddToCartFormComponent::SYLIUS_SHOP_VARIANT_CHANGED)]
    public function updateProductVariant(#[LiveArg] mixed $variantId): void
    {
        $this->variantId = null !== $variantId ? (int) $variantId : null;
    }

    #[LiveAction]
    public function add(#[LiveArg] ?int $wishlistId = null): void
    {
        $product = $this->getProduct();
        if ($product === null) {
            return;
        }

        $wishlist = $wishlistId !== null
            ? $this->wishlistsResolver->resolveById($wishlistId)
            : $this->wishlistsResolver->resolveAndCreate()[0] ?? null;

        if ($wishlist === null) {
            return;
        }

        if ($this->variantId !== null) {
            $command = new AddProductVariantToWishlist($this->variantId);
        } else {
            $command = new AddProductToWishlist($product->getId());
        }

        $command->setWishlist($wishlist);
        $this->commandBus->dispatch($command);

        $this->isInWishlist = true;
    }

    #[LiveAction]
    public function remove(#[LiveArg] ?int $wishlistId = null): void
    {
        $product = $this->getProduct();
        if ($product === null) {
            return;
        }

        $wishlist = $wishlistId !== null
            ? $this->wishlistsResolver->resolveById($wishlistId)
            : $this->wishlistsResolver->resolve()[0] ?? null;

        if ($wishlist === null) {
            return;
        }

        $command = new RemoveProductFromWishlist($product->getId());
        $command->setWishlist($wishlist);
        $this->commandBus->dispatch($command);

        $this->isInWishlist = $this->wishlistProductChecker->isProductInAnyWishlist(
            $product,
            $this->getWishlists(),
        );
    }

    #[ExposeInTemplate(name: 'product')]
    public function getProduct(): ?ProductInterface
    {
        if ($this->productId === null) {
            return null;
        }

        if ($this->product === null) {
            $this->product = $this->productRepository->find($this->productId);
        }

        return $this->product;
    }

    /** @return WishlistInterface[] */
    #[ExposeInTemplate(name: 'wishlists')]
    public function getWishlists(): array
    {
        return $this->wishlistsResolver->resolve();
    }
}
