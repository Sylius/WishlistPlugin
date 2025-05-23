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

namespace spec\Sylius\WishlistPlugin\CommandHandler\Wishlist;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Command\Wishlist\CreateNewWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateNewWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Exception\WishlistNameIsTakenException;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Sylius\WishlistPlugin\Resolver\WishlistCookieTokenResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CreateNewWishlistHandlerSpec extends ObjectBehavior
{
    public function let(
        WishlistRepositoryInterface $wishlistRepository,
        TokenStorageInterface $tokenStorage,
        WishlistFactoryInterface $wishlistFactory,
        WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
        ChannelRepositoryInterface $channelRepository,
        TokenUserResolverInterface $tokenUserResolver,
    ): void {
        $this->beConstructedWith(
            $wishlistRepository,
            $tokenStorage,
            $wishlistFactory,
            $wishlistCookieTokenResolver,
            $channelRepository,
            $tokenUserResolver,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CreateNewWishlistHandler::class);
    }

    public function it_creates_new_wishlist_for_user(
        WishlistRepositoryInterface $wishlistRepository,
        TokenStorageInterface $tokenStorage,
        WishlistFactoryInterface $wishlistFactory,
        WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
        ChannelRepositoryInterface $channelRepository,
        TokenInterface $token,
        ShopUserInterface $shopUser,
        WishlistInterface $wishlist,
        WishlistInterface $existingWishlist,
        ChannelInterface $channel,
        TokenUserResolverInterface $tokenUserResolver,
    ): void {
        $wishlists = [$existingWishlist];

        $tokenStorage->getToken()->willReturn($token);
        $tokenUserResolver->resolve($token)->willReturn($shopUser);

        $wishlistCookieTokenResolver->resolve()->willReturn('token');
        $wishlistFactory->createForUser($shopUser)->willReturn($wishlist);

        $wishlist->getShopUser()->willReturn($shopUser);
        $shopUser->getId()->willReturn(1);
        $wishlistRepository->findAllByShopUser(1)->willReturn($wishlists);

        $wishlist->setToken('token')->shouldBeCalled();
        $channelRepository->findOneByCode('test_channel_code')->willReturn($channel);
        $wishlist->setChannel($channel)->shouldBeCalled();

        $existingWishlist->getName()->willReturn('existing');
        $wishlist->setName('New wishlist');

        $wishlistRepository->add($wishlist)->shouldBeCalled();
        $wishlist->getId()->willReturn(1);

        $createNewWishlist = new CreateNewWishlist('New wishlist', 'test_channel_code');

        $this->__invoke($createNewWishlist);
    }

    public function it_creates_new_wishlist_for_guest(
        WishlistRepositoryInterface $wishlistRepository,
        TokenStorageInterface $tokenStorage,
        WishlistFactoryInterface $wishlistFactory,
        WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
        ChannelRepositoryInterface $channelRepository,
        WishlistInterface $newWishlist,
        ChannelInterface $channel,
        TokenUserResolverInterface $tokenUserResolver,
    ): void {
        $tokenStorage->getToken()->willReturn(null);
        $tokenUserResolver->resolve(null)->willReturn(null);

        $wishlistCookieTokenResolver->resolve()->willReturn('token');
        $wishlistFactory->createNew()->willReturn($newWishlist);

        $wishlistRepository->findAllByAnonymous('token')->willReturn([]);

        $newWishlist->setToken('token')->shouldBeCalled();
        $newWishlist->setName('New wishlist')->shouldBeCalled();
        $channelRepository->findOneByCode('test_channel_code')->willReturn($channel);
        $newWishlist->setChannel($channel)->shouldBeCalled();

        $wishlistRepository->add($newWishlist)->shouldBeCalled();
        $newWishlist->getId()->willReturn(1);

        $createNewWishlist = new CreateNewWishlist('New wishlist', 'test_channel_code');

        $this->__invoke($createNewWishlist);
    }

    public function it_doesnt_add_duplicated_wishlist_name_for_user(
        WishlistRepositoryInterface $wishlistRepository,
        TokenStorageInterface $tokenStorage,
        WishlistFactoryInterface $wishlistFactory,
        WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
        ChannelRepositoryInterface $channelRepository,
        TokenInterface $token,
        ShopUserInterface $shopUser,
        WishlistInterface $wishlist,
        WishlistInterface $existingWishlist,
        ChannelInterface $channel,
        TokenUserResolverInterface $tokenUserResolver,
    ): void {
        $wishlists = [$existingWishlist];

        $tokenStorage->getToken()->willReturn($token);
        $tokenUserResolver->resolve($token)->willReturn($shopUser);

        $wishlistCookieTokenResolver->resolve()->willReturn('token');
        $wishlistFactory->createForUser($shopUser)->willReturn($wishlist);

        $wishlist->getShopUser()->willReturn($shopUser);
        $shopUser->getId()->willReturn(1);
        $wishlistRepository->findAllByShopUser(1)->willReturn($wishlists);

        $wishlist->setToken('token')->shouldBeCalled();
        $channelRepository->findOneByCode('test_channel_code')->willReturn($channel);
        $wishlist->setChannel($channel)->shouldBeCalled();

        $existingWishlist->getName()->willReturn('existing');
        $wishlist->setName('existing')->shouldNotBeCalled();

        $wishlistRepository->add($wishlist)->shouldNotBeCalled();

        $createNewWishlist = new CreateNewWishlist('existing', 'test_channel_code');

        $this
            ->shouldThrow(WishlistNameIsTakenException::class)
            ->during('__invoke', [$createNewWishlist])
        ;
    }
}
