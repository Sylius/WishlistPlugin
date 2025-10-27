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

use Dompdf\Options;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Factory\DomPdfOptionsFactory;
use Sylius\WishlistPlugin\Factory\DomPdfOptionsFactoryInterface;

final class DomPdfOptionsFactoryTest extends TestCase
{
    private DomPdfOptionsFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DomPdfOptionsFactory();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(DomPdfOptionsFactory::class, $this->factory);
    }

    public function testShouldImplementDomPdfOptionsFactoryInterface(): void
    {
        $this->assertInstanceOf(DomPdfOptionsFactoryInterface::class, $this->factory);
    }

    public function testShouldCreateNewDomPdfOptions(): void
    {
        $this->assertInstanceOf(
            Options::class,
            $this->factory->createNew(),
        );
    }
}
