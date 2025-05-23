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

namespace Sylius\WishlistPlugin\Controller\Action\ApiPlatform;

use Sylius\WishlistPlugin\Command\Wishlist\RemoveWishlist;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RemoveWishlistAction
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $wishlistToken = (string) $request->attributes->get('token');

        $removeWishlist = new RemoveWishlist($wishlistToken);
        $this->messageBus->dispatch($removeWishlist);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
