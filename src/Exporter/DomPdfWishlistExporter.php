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

namespace Sylius\WishlistPlugin\Exporter;

use Doctrine\Common\Collections\Collection;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Sylius\WishlistPlugin\Factory\DomPdfFactoryInterface;
use Twig\Environment;

final readonly class DomPdfWishlistExporter implements DomPdfWishlistExporterInterface
{
    public function __construct(
        private Environment|TwigToPdfRendererInterface $twigRenderer,
        private ?DomPdfFactoryInterface $domPdfFactory = null,
    ) {
        if ($this->twigRenderer instanceof Environment) {
            trigger_deprecation(
                'sylius/wishlist-plugin',
                '1.2',
                'Passing an instance of %s to %s is deprecated and it will not be supported in 2.0, use an instance of %s instead.',
                Environment::class,
                self::class,
                TwigToPdfRendererInterface::class,
            );
        }
        if (null !== $this->domPdfFactory) {
            trigger_deprecation(
                'sylius/wishlist-plugin',
                '1.2',
                'Passing an instance of %s to %s is deprecated and the argument will be removed in 2.0.',
                DomPdfFactoryInterface::class,
                self::class,
            );
        }
    }

    public function export(Collection $data): string
    {
        $templateParams = [
            'title' => 'My wishlist products',
            'date' => (new \DateTime())->format('d.m.Y'),
            'products' => $data,
        ];

        if ($this->twigRenderer instanceof TwigToPdfRendererInterface) {
            return $this->twigRenderer->render(
                '@SyliusWishlistPlugin/wishlist_pdf.html.twig',
                $templateParams,
                'sylius_wishlist',
            );
        }

        // Legacy path
        $dompdf = $this->domPdfFactory->createNewWithDefaultOptions();
        $html = $this->twigRenderer->render('@SyliusWishlistPlugin/wishlist_pdf.html.twig', $templateParams);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Wishlist', ['Attachment' => true]);

        return ''; // Legacy path streams directly, return is unused
    }
}
