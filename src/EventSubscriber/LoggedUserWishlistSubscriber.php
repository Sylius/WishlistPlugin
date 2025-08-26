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

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final readonly class LoggedUserWishlistSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SectionProviderInterface $uriBasedSectionContext,
        private WishlistsResolverInterface $wishlistsResolver,
        private EntityManagerInterface $entityManager,
        private WishlistRepositoryInterface $wishlistRepository,
        private RequestStack $requestStack,
        private string $wishlistCookieToken,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onImplicitLogin(UserEvent $event): void
    {
        if (!$this->uriBasedSectionContext->getSection() instanceof ShopSection) {
            return;
        }

        $user = $event->getUser();
        if (!$user instanceof ShopUserInterface) {
            return;
        }

        $this->saveWishlistsForLoggedUser($user);
    }

    public function onInteractiveLogin(InteractiveLoginEvent $interactiveLoginEvent): void
    {
        $section = $this->uriBasedSectionContext->getSection();
        if (!$section instanceof ShopSection) {
            return;
        }

        $user = $interactiveLoginEvent->getAuthenticationToken()->getUser();
        if (!$user instanceof ShopUserInterface) {
            return;
        }

        $this->saveWishlistsForLoggedUser($user);
    }

    private function saveWishlistsForLoggedUser(ShopUserInterface $user): void
    {
        $wishlists = $this->wishlistsResolver->resolve();

        if (0 === count($wishlists)) {
            return;
        }

        /** @var WishlistInterface $wishlist */
        foreach ($wishlists as $wishlist) {
            if ($this->shouldSkipWishlist($wishlist, $user)) {
                continue;
            }

            $existingWishlist = $this->findExistingWishlist($wishlist, $user);

            if (null !== $existingWishlist && $existingWishlist->getId() !== $wishlist->getId()) {
                $this->mergeWishlists($wishlist, $existingWishlist);

                continue;
            }

            $wishlist->setShopUser($user);
        }

        $this->entityManager->flush();
        $this->clearWishlistCookie();
    }

    private function shouldSkipWishlist(WishlistInterface $wishlist, ShopUserInterface $user): bool
    {
        $wishlistShopUser = $wishlist->getShopUser();

        if (null !== $wishlistShopUser && $wishlistShopUser->getId() !== $user->getId()) {
            return true;
        }

        if (0 === $wishlist->getWishlistProducts()->count()) {
            return true;
        }

        return false;
    }

    private function findExistingWishlist(WishlistInterface $wishlist, ShopUserInterface $user): ?WishlistInterface
    {
        if (null === $wishlist->getName()) {
            return null;
        }

        return $this->wishlistRepository->findOneByShopUserAndName($user, $wishlist->getName());
    }

    private function mergeWishlists(WishlistInterface $source, WishlistInterface $target): void
    {
        foreach ($source->getWishlistProducts() as $wishlistProduct) {
            $variant = $wishlistProduct->getVariant();

            if (!$target->hasProductVariant($variant)) {
                $target->addWishlistProduct($wishlistProduct);
            }
        }

        $this->entityManager->remove($source);
        $this->entityManager->persist($target);
    }

    private function clearWishlistCookie(): void
    {
        $mainRequest = $this->requestStack->getMainRequest();

        if (null === $mainRequest) {
            return;
        }

        $mainRequest->cookies->remove($this->wishlistCookieToken);
        setcookie($this->wishlistCookieToken, '', -1, '/');
    }
}
