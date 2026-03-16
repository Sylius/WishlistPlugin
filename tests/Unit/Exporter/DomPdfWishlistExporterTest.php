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

namespace Tests\Sylius\WishlistPlugin\Unit\Exporter;

use Doctrine\Common\Collections\ArrayCollection;
use Dompdf\Dompdf;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporter;
use Sylius\WishlistPlugin\Exporter\DomPdfWishlistExporterInterface;
use Sylius\WishlistPlugin\Factory\DomPdfFactoryInterface;
use Sylius\WishlistPlugin\Model\VariantPdfModelInterface;
use Twig\Environment;

final class DomPdfWishlistExporterTest extends TestCase
{
    private MockObject&Environment $environment;

    private MockObject&DomPdfFactoryInterface $factory;

    private DomPdfWishlistExporter $exporter;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->factory = $this->createMock(DomPdfFactoryInterface::class);
        $this->exporter = new DomPdfWishlistExporter(
            $this->environment,
            $this->factory,
        );
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(DomPdfWishlistExporter::class, $this->exporter);
    }

    public function testShouldImplementDomPdfWishlistExporterInterface(): void
    {
        $this->assertInstanceOf(DomPdfWishlistExporterInterface::class, $this->exporter);
    }

    public function testShouldReturnPdfAsAttachment(): void
    {
        $domPdf = $this->createMock(Dompdf::class);
        $data = new ArrayCollection([
            $this->createMock(VariantPdfModelInterface::class),
        ]);
        $this->factory->expects($this->once())->method('createNewWithDefaultOptions')->willReturn($domPdf);
        $this->environment->expects($this->once())->method('render')->with('@SyliusWishlistPlugin/wishlist_pdf.html.twig', ['title' => 'My wishlist products', 'date' => date('d.m.Y'), 'products' => $data])->willReturn('');
        $domPdf->expects($this->once())->method('loadHtml')->with('');
        $domPdf->expects($this->once())->method('setPaper')->with('A4', 'portrait');
        $domPdf->expects($this->once())->method('render');
        $domPdf->expects($this->once())->method('stream')->with('Wishlist', ['Attachment' => true]);

        $result = $this->exporter->export($data);

        $this->assertSame('', $result);
    }

    public function testShouldRenderPdfViaPdfBundle(): void
    {
        $twigToPdfRenderer = $this->createMock(TwigToPdfRendererInterface::class);
        $exporter = new DomPdfWishlistExporter($twigToPdfRenderer);

        $data = new ArrayCollection([
            $this->createMock(VariantPdfModelInterface::class),
        ]);

        $expectedParams = [
            'title' => 'My wishlist products',
            'date' => date('d.m.Y'),
            'products' => $data,
        ];

        $twigToPdfRenderer->expects($this->once())
            ->method('render')
            ->with(
                '@SyliusWishlistPlugin/wishlist_pdf.html.twig',
                $expectedParams,
                'sylius_wishlist',
            )
            ->willReturn('%PDF-content%');

        $result = $exporter->export($data);

        $this->assertSame('%PDF-content%', $result);
    }
}
