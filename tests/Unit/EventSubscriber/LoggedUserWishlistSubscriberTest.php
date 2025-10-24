<?php

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
use Sylius\WishlistPlugin\Resolver\WishlistsResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LoggedUserWishlistSubscriberTest extends TestCase
{
    private MockObject&SectionProviderInterface $uriBasedSectionContext;

    private MockObject&WishlistsResolverInterface $wishlistsResolver;

    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&UserEvent $event;

    private MockObject&ShopSection $shopSection;

    private LoggedUserWishlistSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->uriBasedSectionContext = $this->createMock(SectionProviderInterface::class);
        $this->wishlistsResolver = $this->createMock(WishlistsResolverInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->event = $this->createMock(UserEvent::class);
        $this->shopSection = $this->createMock(ShopSection::class);
        $this->subscriber = new LoggedUserWishlistSubscriber(
            $this->uriBasedSectionContext,
            $this->wishlistsResolver,
            $this->entityManager,
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
        $this->wishlistsResolver->expects($this->never())->method('resolve');

        $this->subscriber->onImplicitLogin($this->event);
    }

    public function testShouldAssignShopUserToWishlistsWithoutStopUserOnLogin(): void
    {
        $firstWishlist = $this->createMock(WishlistInterface::class);
        $secondWishlist = $this->createMock(WishlistInterface::class);
        $firstShopUser = $this->createMock(ShopUserInterface::class);
        $secondShopUser = $this->createMock(ShopUserInterface::class);

        $this->uriBasedSectionContext->expects($this->once())->method('getSection')->willReturn($this->shopSection);
        $this->event->expects($this->once())->method('getUser')->willReturn($firstShopUser);
        $this->wishlistsResolver->expects($this->once())->method('resolve')->willReturn([$firstWishlist, $secondWishlist]);
        $firstShopUser->expects($this->once())->method('getId')->willReturn(1);
        $firstWishlist->expects($this->once())->method('getShopUser')->willReturn($secondShopUser);
        $secondShopUser->expects($this->once())->method('getId')->willReturn(15);
        $firstWishlist->expects($this->never())->method('setShopUser')->with($firstShopUser);
        $secondWishlist->expects($this->once())->method('getShopUser')->willReturn(null);
        $secondWishlist->expects($this->once())->method('setShopUser')->with($firstShopUser);
        $this->entityManager->expects($this->once())->method('flush');

        $this->subscriber->onImplicitLogin($this->event);
    }
}
