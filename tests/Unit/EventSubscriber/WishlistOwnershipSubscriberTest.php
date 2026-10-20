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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\EventSubscriber\WishlistOwnershipSubscriber;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Voter\WishlistVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class WishlistOwnershipSubscriberTest extends TestCase
{
    private MockObject&WishlistRepositoryInterface $wishlistRepository;

    private MockObject&AuthorizationCheckerInterface $authorizationChecker;

    private MockObject&WishlistInterface $wishlist;

    private WishlistOwnershipSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->wishlistRepository = $this->createMock(WishlistRepositoryInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->wishlist = $this->createMock(WishlistInterface::class);
        $this->subscriber = new WishlistOwnershipSubscriber($this->wishlistRepository, $this->authorizationChecker);
    }

    public function testShouldSubscribeToTheRequestAfterTheFirewall(): void
    {
        $this->assertSame([KernelEvents::REQUEST => ['denyForeignWishlist', 0]], WishlistOwnershipSubscriber::getSubscribedEvents());
    }

    #[DataProvider('provideWishlistRoutes')]
    public function testShouldThrow404WhenTheWishlistBelongsToSomeoneElse(string $route, string $attribute): void
    {
        $this->wishlistRepository->expects($this->once())->method('find')->with(7)->willReturn($this->wishlist);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->with(WishlistVoter::UPDATE, $this->wishlist)->willReturn(false);

        $this->expectException(NotFoundHttpException::class);

        $this->subscriber->denyForeignWishlist($this->event($route, [$attribute => '7']));
    }

    public function testShouldLetTheOwnerThrough(): void
    {
        $this->wishlistRepository->expects($this->once())->method('find')->with(7)->willReturn($this->wishlist);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->with(WishlistVoter::UPDATE, $this->wishlist)->willReturn(true);

        $this->subscriber->denyForeignWishlist($this->event('sylius_wishlist_plugin_shop_locale_wishlist_clean', ['wishlistId' => '7']));
    }

    public function testShouldLeaveAMissingWishlistToTheController(): void
    {
        $this->wishlistRepository->expects($this->once())->method('find')->with(7)->willReturn(null);
        $this->authorizationChecker->expects($this->never())->method('isGranted');

        $this->subscriber->denyForeignWishlist($this->event('sylius_wishlist_plugin_shop_locale_wishlist_clean', ['wishlistId' => '7']));
    }

    public function testShouldCheckEveryWishlistOfTheRoute(): void
    {
        $destinedWishlist = $this->createMock(WishlistInterface::class);
        $this->wishlistRepository->expects($this->exactly(2))->method('find')->willReturnMap([[1, $this->wishlist], [2, $destinedWishlist]]);
        $this->authorizationChecker->expects($this->exactly(2))->method('isGranted')->willReturnCallback(
            fn (string $attribute, WishlistInterface $wishlist): bool => $wishlist === $this->wishlist,
        );

        $this->expectException(NotFoundHttpException::class);

        $this->subscriber->denyForeignWishlist($this->event(
            'sylius_wishlist_plugin_shop_wishlist_copy_selected_products_to_other_wishlist',
            ['wishlistId' => '1', 'destinedWishlistId' => '2'],
        ));
    }

    #[DataProvider('provideIgnoredRoutes')]
    public function testShouldIgnoreRoutesThatAreNotGuarded(string $route): void
    {
        $this->wishlistRepository->expects($this->never())->method('find');

        $this->subscriber->denyForeignWishlist($this->event($route, ['id' => '7']));
    }

    public static function provideWishlistRoutes(): iterable
    {
        yield 'locale route' => ['sylius_wishlist_plugin_shop_locale_wishlist_clean', 'wishlistId'];
        yield 'unprefixed route' => ['sylius_wishlist_plugin_shop_wishlist_remove_wishlist', 'id'];
        yield 'destination' => ['sylius_wishlist_plugin_shop_wishlist_copy_selected_products_to_other_wishlist', 'destinedWishlistId'];
    }

    public static function provideIgnoredRoutes(): iterable
    {
        yield 'guest wishlist claim' => ['sylius_wishlist_plugin_shop_locale_wishlist_add_wishlist_to_user'];
        yield 'wishlist page' => ['sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist'];
        yield 'another bundle' => ['sylius_shop_account_address_book_delete'];
    }

    private function event(string $route, array $attributes): RequestEvent
    {
        $request = new Request();
        $request->attributes->add(['_route' => $route] + $attributes);

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
