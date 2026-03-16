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

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\WishlistPlugin\Command\Wishlist\WishlistItem;
use Sylius\WishlistPlugin\Processor\WishlistCommandProcessorInterface;
use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateWishlistProductsQuantityAction extends BaseWishlistProductsAction
{
    public function __construct(
        CartContextInterface $cartContext,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        WishlistCommandProcessorInterface $wishlistCommandProcessor,
        MessageBusInterface $messageBus,
        UrlGeneratorInterface $urlGenerator,
        WishlistRepositoryInterface $wishlistRepository,
        TranslatorInterface $translator,
        private EntityManagerInterface $wishlistProductManager,
    ) {
        parent::__construct(
            $cartContext,
            $formFactory,
            $requestStack,
            $wishlistCommandProcessor,
            $messageBus,
            $urlGenerator,
            $wishlistRepository,
            $translator,
        );
    }

    protected function handleCommand(FormInterface $form): Response|null
    {
        foreach ($form->get('items') as $itemForm) {
            /** @var WishlistItem $wishlistItem */
            $wishlistItem = $itemForm->getData();
            $wishlistProduct = $wishlistItem->getWishlistProduct();

            if (null === $wishlistProduct) {
                continue;
            }

            $wishlistProduct->setQuantity($wishlistItem->getOrderItemQuantity());
            $this->wishlistProductManager->persist($wishlistProduct);
        }

        $this->wishlistProductManager->flush();

        $this->getFlashBag()->add('success', $this->translator->trans('sylius_wishlist_plugin.ui.changes_saved'));

        return null;
    }
}
