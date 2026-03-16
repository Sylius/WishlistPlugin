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
    protected function handleCommand(FormInterface $form): Response|null
    {
        $command = new ExportSelectedProductsFromWishlistToPdf($form->getData());
        $envelope = $this->messageBus->dispatch($command);

        $handledStamp = $envelope->last(HandledStamp::class);
        $pdfContent = $handledStamp?->getResult() ?? '';

        if ('' === $pdfContent) {
            // Legacy path — dompdf already streamed, exit to prevent timeout
            exit();
        }

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Wishlist.pdf"',
        ]);
    }
}
