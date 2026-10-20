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

namespace Sylius\WishlistPlugin\EventSubscriber;

use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Voter\WishlistVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class WishlistOwnershipSubscriber implements EventSubscriberInterface
{
    private const ROUTE_PREFIX = 'sylius_wishlist_plugin_shop_';

    private const SELF_GUARDED_ROUTE_SUFFIXES = ['wishlist_add_wishlist_to_user', 'wishlist_show_chosen_wishlist'];

    private const WISHLIST_ID_ATTRIBUTES = ['wishlistId', 'destinedWishlistId', 'id'];

    public function __construct(
        private WishlistRepositoryInterface $wishlistRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['denyForeignWishlist', 0]];
    }

    public function denyForeignWishlist(RequestEvent $event): void
    {
        $attributes = $event->getRequest()->attributes;
        $route = (string) $attributes->get('_route');

        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        foreach (self::SELF_GUARDED_ROUTE_SUFFIXES as $suffix) {
            if (str_ends_with($route, $suffix)) {
                return;
            }
        }

        foreach (self::WISHLIST_ID_ATTRIBUTES as $attribute) {
            if (!$attributes->has($attribute)) {
                continue;
            }

            $wishlist = $this->wishlistRepository->find($attributes->getInt($attribute));

            if ($wishlist instanceof WishlistInterface && !$this->authorizationChecker->isGranted(WishlistVoter::UPDATE, $wishlist)) {
                throw new NotFoundHttpException('Wishlist not found.');
            }
        }
    }
}
