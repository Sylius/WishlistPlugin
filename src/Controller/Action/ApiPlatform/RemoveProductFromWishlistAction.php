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

use Sylius\WishlistPlugin\Command\Wishlist\RemoveProductFromWishlist;
use Sylius\WishlistPlugin\Exception\WishlistNotFoundException;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\WishlistPlugin\Voter\WishlistVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class RemoveProductFromWishlistAction
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private WishlistRepositoryInterface $wishlistRepository,
        private Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $wishlistToken = (string) $request->attributes->get('token');
        $productId = (int) $request->attributes->get('productId');

        $wishlist = $this->wishlistRepository->findByToken($wishlistToken);

        if (null === $wishlist) {
            throw new WishlistNotFoundException(
                sprintf('The Wishlist with token %s does not exist', $wishlistToken),
            );
        }

        if (!$this->security->isGranted(WishlistVoter::DELETE, $wishlist)) {
            throw new AccessDeniedException();
        }

        $command = new RemoveProductFromWishlist($productId);
        $command->setWishlist($wishlist);
        $this->messageBus->dispatch($command);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
