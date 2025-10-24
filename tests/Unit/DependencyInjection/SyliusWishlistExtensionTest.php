<?php

declare(strict_types=1);

namespace Tests\Sylius\WishlistPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\WishlistPlugin\DependencyInjection\SyliusWishlistExtension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class SyliusWishlistExtensionTest extends TestCase
{
    private SyliusWishlistExtension $syliusWishlistExtension;

    protected function setUp(): void
    {
        $this->syliusWishlistExtension = new SyliusWishlistExtension();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(SyliusWishlistExtension::class, $this->syliusWishlistExtension);
    }

    public function testShouldImplementPrependExtentionInterface(): void
    {
        $this->assertInstanceOf(PrependExtensionInterface::class, $this->syliusWishlistExtension);
    }

    public function testShouldExtendAbstractResourceExtension(): void
    {
        $this->assertInstanceOf(AbstractResourceExtension::class, $this->syliusWishlistExtension);
    }
}
