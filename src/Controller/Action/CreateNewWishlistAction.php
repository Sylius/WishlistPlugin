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

namespace Sylius\WishlistPlugin\Controller\Action;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\WishlistPlugin\Command\Wishlist\CreateNewWishlist;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CreateNewWishlistAction
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private ChannelContextInterface $channelContext,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $wishlistName = $request->get('create_new_wishlist')['name'];

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException $exception) {
            $channel = null;
        }

        try {
            if (null !== $channel) {
                $createNewWishlist = new CreateNewWishlist($wishlistName, $channel->getCode());
            } else {
                $createNewWishlist = new CreateNewWishlist($wishlistName, null);
            }

            $envelope = $this->commandBus->dispatch($createNewWishlist);
            /** @var HandledStamp $handledStamp */
            $handledStamp = $envelope->last(HandledStamp::class);
            $result = $handledStamp->getResult();

            /** @var Session $session */
            $session = $this->requestStack->getSession();

            $session->getFlashBag()->add(
                'success',
                $this->translator->trans('sylius_wishlist_plugin.ui.create_new_wishlist'),
            );
        } catch (HandlerFailedException) {
            /** @var Session $session */
            $session = $this->requestStack->getSession();

            $session->getFlashBag()->add(
                'error',
                $this->translator->trans('sylius_wishlist_plugin.ui.wishlist_name_already_exists'),
            );

            return new JsonResponse([]);
        }

        return new JsonResponse(
            [
            'url' => $this
                ->urlGenerator
                ->generate(
                    'sylius_wishlist_plugin_shop_locale_wishlist_show_chosen_wishlist',
                    ['wishlistId' => $result],
                )],
        );
    }
}
