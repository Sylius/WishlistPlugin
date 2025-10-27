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

namespace Tests\Sylius\WishlistPlugin\Unit\Factory;

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Factory\DomPdfFactory;
use Sylius\WishlistPlugin\Factory\DomPdfFactoryInterface;
use Sylius\WishlistPlugin\Factory\DomPdfOptionsFactoryInterface;

final class DomPdfFactoryTest extends TestCase
{
    private MockObject&DomPdfOptionsFactoryInterface $domPdfOptionsFactory;

    private DomPdfFactory $factory;

    protected function setUp(): void
    {
        $this->domPdfOptionsFactory = $this->createMock(DomPdfOptionsFactoryInterface::class);
        $this->factory = new DomPdfFactory($this->domPdfOptionsFactory);
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(DomPdfFactory::class, $this->factory);
    }

    public function testShouldImplementDomPdfFactoryInterface(): void
    {
        $this->assertInstanceOf(DomPdfFactoryInterface::class, $this->factory);
    }

    public function testShouldCreateNewDomPdf(): void
    {
        $this->assertInstanceOf(DomPdf::class, $this->factory->createNew());
    }

    public function testShouldCreateNewDomPdfWithDefaultOptions(): void
    {
        $pdfOptions = $this->createMock(Options::class);
        $this->domPdfOptionsFactory->expects($this->once())->method('createNew')->willReturn($pdfOptions);
        $setInvokeCount = $this->exactly(2);
        $pdfOptions->expects($setInvokeCount)->method('set')->willReturnCallback(function (string $key, mixed $value) use ($setInvokeCount) {
            match ($setInvokeCount->numberOfInvocations()) {
                1 => $this->assertSame('isRemoteEnabled', $key) && $this->assertTrue($value),
                2 => $this->assertSame('defaultFont', $key) && $this->assertSame('Arial', $value),
            };
        });
        $pdfOptions->expects($this->once())->method('getHttpContext')->willReturn(['http' => []]);

        $this->assertSame(
            $pdfOptions,
            $this->factory->createNewWithDefaultOptions()->getOptions(),
        );
    }
}
