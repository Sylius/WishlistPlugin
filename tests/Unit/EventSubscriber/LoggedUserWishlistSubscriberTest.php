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

namespace Tests\Sylius\WishlistPlugin\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\AdminBundle\SectionResolver\AdminSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\EventSubscriber\LoggedUserWishlistSubscriber;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class LoggedUserWishlistSubscriberTest extends TestCase
{
    private MockObject&SectionProviderInterface $uriBasedSectionContext;

    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&RequestStack $requestStack;

    private MockObject&WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver;

    private MockObject&UserEvent $event;

    private MockObject&ShopSection $shopSection;

    private LoggedUserWishlistSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->uriBasedSectionContext = $this->createMock(SectionProviderInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->wishlistCookieTokenResolver = $this->createMock(WishlistCookieTokenResolverInterface::class);
        $this->event = $this->createMock(UserEvent::class);
        $this->shopSection = $this->createMock(ShopSection::class);
        $this->subscriber = new LoggedUserWishlistSubscriber(
            $this->uriBasedSectionContext,
            null,
            $this->entityManager,
            $this->wishlistRepository,
            $this->requestStack,
            'sylius_wishlist_token',
            $this->wishlistCookieTokenResolver,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(LoggedUserWishlistSubscriber::class, $this->subscriber);
    }

    public function testShouldImplementEventSubscriberInterface(): void
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
    }

    public function testShouldReturnIfSectionIsInvalidOnImplicitLogin(): void
    {
        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->createMock(AdminSection::class));
        $this->event->expects($this->never())->method('getUser');

        $this->subscriber->onImplicitLogin($this->event);
    }

    public function testShouldReturnIfUserIsInvalidOnImplicitLogin(): void
    {
        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($this->createMock(AdminUserInterface::class));
        $this->wishlistRepository->expects($this->never())->method('findAllByAnonymous');

        $this->subscriber->onImplicitLogin($this->event);
    }

    public function testShouldAssignShopUserToWishlistsWithoutStopUserOnLogin(): void
    {
        $firstWishlist = $this->createMock(WishlistInterface::class);
        $secondWishlist = $this->createMock(WishlistInterface::class);
        $firstShopUser = $this->createMock(ShopUserInterface::class);
        $secondShopUser = $this->createMock(ShopUserInterface::class);
        $wishlistProducts = new \Doctrine\Common\Collections\ArrayCollection([new \stdClass()]);

        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($firstShopUser);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('guest_token');
        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn(null);
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymous')->with('guest_token')->willReturn([$firstWishlist, $secondWishlist]);
        $firstShopUser->expects($this->once())->method('getId')->willReturn(1);

        // First wishlist has different user - should be skipped
        $firstWishlist->expects($this->once())->method('getShopUser')->willReturn($secondShopUser);
        $secondShopUser->expects($this->once())->method('getId')->willReturn(15);
        $firstWishlist->expects($this->never())->method('setShopUser')->with($firstShopUser);
        $firstWishlist->expects($this->never())->method('getWishlistProducts');

        // Second wishlist has no user and has products - should be assigned
        $secondWishlist->expects($this->once())->method('getShopUser')->willReturn(null);
        $secondWishlist->expects($this->once())->method('getWishlistProducts')->willReturn($wishlistProducts);
        $secondWishlist->expects($this->once())->method('getName')->willReturn(null);
        $secondWishlist->expects($this->once())->method('setShopUser')->with($firstShopUser);

        $this->entityManager->expects($this->once())->method('flush');

        $this->subscriber->onImplicitLogin($this->event);
    }

    public function testShouldNotAssignAnythingIfThereAreNoGuestWishlistsOnLogin(): void
    {
        $shopUser = $this->createMock(ShopUserInterface::class);

        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($shopUser);
        $this->wishlistCookieTokenResolver->expects($this->once())->method('resolve')->willReturn('guest_token');
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymous')->with('guest_token')->willReturn([]);
        $this->entityManager->expects($this->never())->method('flush');

        $this->subscriber->onImplicitLogin($this->event);
    }

    public function testShouldAssignGuestWishlistsFoundByWishlistCookieOnLoginWithoutWishlistCookieTokenResolver(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $shopUser = $this->createMock(ShopUserInterface::class);

        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($shopUser);
        $this->requestStack->expects($this->exactly(2))->method('getMainRequest')->willReturnOnConsecutiveCalls(
            new Request(cookies: ['sylius_wishlist_token' => 'guest_token']),
            null,
        );
        $this->wishlistRepository->expects($this->once())->method('findAllByAnonymous')->with('guest_token')->willReturn([$wishlist]);
        $wishlist->expects($this->once())->method('getShopUser')->willReturn(null);
        $wishlist->expects($this->once())->method('getWishlistProducts')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([new \stdClass()]));
        $wishlist->expects($this->once())->method('getName')->willReturn(null);
        $wishlist->expects($this->once())->method('setShopUser')->with($shopUser);
        $this->entityManager->expects($this->once())->method('flush');

        $this->createSubscriberWithoutWishlistCookieTokenResolver()->onImplicitLogin($this->event);
    }

    public function testShouldNotLookUpGuestWishlistsWithoutWishlistCookieOnLoginWithoutWishlistCookieTokenResolver(): void
    {
        $shopUser = $this->createMock(ShopUserInterface::class);

        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($shopUser);
        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn(new Request());
        $this->wishlistRepository->expects($this->never())->method('findAllByAnonymous');
        $this->entityManager->expects($this->never())->method('flush');

        $this->createSubscriberWithoutWishlistCookieTokenResolver()->onImplicitLogin($this->event);
    }

    private function createSubscriberWithoutWishlistCookieTokenResolver(): LoggedUserWishlistSubscriber
    {
        return new LoggedUserWishlistSubscriber(
            $this->uriBasedSectionContext,
            null,
            $this->entityManager,
            $this->wishlistRepository,
            $this->requestStack,
            'sylius_wishlist_token',
        );
    }
}
