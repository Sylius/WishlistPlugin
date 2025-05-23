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

namespace spec\Sylius\WishlistPlugin\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Dompdf\Dompdf;
use PhpSpec\ObjectBehavior;
use Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporter;
use Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporterInterface;
use Sylius\WishlistPlugin\Factory\DomPdfFactoryInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;
use Twig\Environment;

final class DomPdfWishlistExporterSpec extends ObjectBehavior
{
    public function let(
        Environment $twigEnvironment,
        DomPdfFactoryInterface $domPdfFactory,
    ) {
        $this->beConstructedWith(
            $twigEnvironment,
            $domPdfFactory,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(DomPdfWishlistExporter::class);
        $this->shouldImplement(DomPdfWishlistExporterInterface::class);
    }

    public function it_returns_pdf_as_attachment(
        Environment $twigEnvironment,
        VariantPdfModelInterface $pdfModel,
        Dompdf $dompdf,
        DomPdfFactoryInterface $domPdfFactory,
    ): void {
        $domPdfFactory->createNewWithDefaultOptions()->willReturn($dompdf);

        $data = new ArrayCollection([
            $pdfModel->getWrappedObject(),
        ]);

        $html = '';

        $twigEnvironment->render('@SyliusWishlistPlugin/wishlist_pdf.html.twig', [
            'title' => 'My wishlist products',
            'date' => date('d.m.Y'),
            'products' => $data,
        ])->willReturn($html);

        $dompdf->loadHtml($html)->shouldBeCalledOnce();
        $dompdf->setPaper('A4', 'portrait')->shouldBeCalledOnce();
        $dompdf->render()->shouldBeCalledOnce();
        $dompdf->stream('Wishlist', ['Attachment' => true])->shouldBeCalledOnce();

        $this->export($data);
    }
}
