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

use Sylius\WishlistPlugin\Command\Wishlist\ExportSelectedProductsFromWishlistToPdf;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class ExportWishlistToPdfAction extends BaseWishlistProductsAction
{
    private ?string $pdfContent = null;

    protected function handleCommand(FormInterface $form): void
    {
        $command = new ExportSelectedProductsFromWishlistToPdf($form->getData());
        $envelope = $this->messageBus->dispatch($command);

        $this->pdfContent = $envelope->last(HandledStamp::class)?->getResult();
    }

    protected function getResponseAfterCommand(int $wishlistId): ?Response
    {
        if (null === $this->pdfContent || '' === $this->pdfContent) {
            // Legacy path — dompdf already streamed, exit to prevent timeout
            exit();
        }

        return new Response($this->pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Wishlist.pdf"',
        ]);
    }
}
