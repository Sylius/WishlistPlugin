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

use Doctrine\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\WishlistPlugin\Command\Wishlist\CreateWishlist;
use Sylius\WishlistPlugin\CommandHandler\Wishlist\CreateWishlistHandler;
use Sylius\WishlistPlugin\Entity\WishlistInterface;
use Sylius\WishlistPlugin\Factory\WishlistFactoryInterface;
use Sylius\WishlistPlugin\Resolver\ShopUserWishlistResolverInterface;
use Sylius\WishlistPlugin\Resolver\TokenUserResolverInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CreateWishlistHandlerSpec extends ObjectBehavior
{
    public function let(
        TokenStorageInterface $tokenStorage,
        WishlistFactoryInterface $wishlistFactory,
        ShopUserWishlistResolverInterface $shopUserWishlistResolver,
        ObjectManager $wishlistManager,
        ChannelRepositoryInterface $channelRepository,
        TokenUserResolverInterface $tokenUserResolver,
        RequestStack $requestStack,
    ): void {
        $this->beConstructedWith(
            $tokenStorage,
            $wishlistFactory,
            $shopUserWishlistResolver,
            $wishlistManager,
            $channelRepository,
            $tokenUserResolver,
            $requestStack,
            'token',
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CreateWishlistHandler::class);
    }

    public function it_creates_new_wishlist_for_user(
        TokenStorage $tokenStorage,
        TokenInterface $token,
        ShopUserInterface $user,
        WishlistFactoryInterface $wishlistFactory,
        WishlistInterface $wishlist,
        ShopUserWishlistResolverInterface $shopUserWishlistResolver,
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel,
        ObjectManager $wishlistManager,
        TokenUserResolverInterface $tokenUserResolver,
        RequestStack $requestStack,
        Request $request,
        ParameterBag $attributes,
    ): void {
        $tokenValue = 'test_token_value';
        $channelCode = 'test_channel_code';

        $createWishlist = new CreateWishlist($tokenValue, $channelCode);

        $tokenStorage->getToken()->willReturn($token);
        $tokenUserResolver->resolve($token)->willReturn($user);

        $wishlistFactory->createNew()->willReturn($wishlist);
        $shopUserWishlistResolver->resolve($user)->willReturn($wishlist);
        $wishlist->setName('Wishlist')->shouldBeCalledOnce();
        $wishlist->setToken($tokenValue)->shouldBeCalledOnce();

        $requestStack->getMainRequest()->willReturn($request)->shouldBeCalledOnce();
        $request->attributes = $attributes;

        $channelRepository->findOneByCode($channelCode)->willReturn($channel);

        $wishlist->setChannel($channel)->shouldBeCalled();

        $wishlistManager->persist($wishlist)->shouldBeCalled();
        $wishlistManager->flush()->shouldBeCalled();

        $this->__invoke($createWishlist);
    }

    public function it_creates_new_wishlist_for_guest_with_missing_channel(
        TokenStorage $tokenStorage,
        TokenInterface $token,
        ShopUserInterface $user,
        WishlistFactoryInterface $wishlistFactory,
        WishlistInterface $wishlist,
        ShopUserWishlistResolverInterface $shopUserWishlistResolver,
        ChannelRepositoryInterface $channelRepository,
        ChannelInterface $channel,
        ObjectManager $wishlistManager,
        TokenUserResolverInterface $tokenUserResolver,
        RequestStack $requestStack,
        Request $request,
        ParameterBag $attributes,
    ): void {
        $tokenValue = 'test_token_value';
        $channelCode = null;

        $createWishlist = new CreateWishlist($tokenValue, $channelCode);

        $tokenStorage->getToken()->willReturn(null);
        $tokenUserResolver->resolve(null)->willReturn(null);

        $wishlistFactory->createNew()->willReturn($wishlist);
        $shopUserWishlistResolver->resolve($user)->shouldNotBeCalled();
        $wishlist->setName('Wishlist')->shouldBeCalledOnce();
        $wishlist->setToken($tokenValue)->shouldBeCalledOnce();

        $requestStack->getMainRequest()->willReturn($request)->shouldBeCalledOnce();
        $request->attributes = $attributes;

        $channelRepository->findOneByCode('test')->willReturn(null);

        $wishlist->setChannel($channel)->shouldNotBeCalled();

        $wishlistManager->persist($wishlist)->shouldBeCalled();
        $wishlistManager->flush()->shouldBeCalled();

        $this->__invoke($createWishlist);
    }
}
