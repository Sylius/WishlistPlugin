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

namespace Tests\Sylius\WishlistPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    public function testShouldBeInitializable(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->configuration);
    }

    public function testShouldImplementConfigurationInterface(): void
    {
        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
    }

    public function testShouldReturnTreeBuilder(): void
    {
        $this->assertInstanceOf(TreeBuilder::class, $this->configuration->getConfigTreeBuilder());
    }
}
