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

namespace Tests\Sylius\WishlistPlugin\Unit\Resolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\WishlistPlugin\Resolver\RefererPathResolver;
use Symfony\Component\HttpFoundation\Request;

final class RefererPathResolverTest extends TestCase
{
    #[DataProvider('provideReferers')]
    public function testShouldKeepOnlyAnOnSitePathAndQuery(?string $referer, string $expected): void
    {
        $request = new Request();

        if (null !== $referer) {
            $request->headers->set('referer', $referer);
        }

        $this->assertSame($expected, (new RefererPathResolver())->resolve($request));
    }

    public static function provideReferers(): iterable
    {
        yield 'listing with its query' => ['https://shop.example/en_US/taxons/t-shirts?page=2', '/en_US/taxons/t-shirts?page=2'];
        yield 'another host' => ['https://evil.example/en_US/phish', '/en_US/phish'];
        yield 'protocol-relative path' => ['https://shop.example//evil.example/phish', '/'];
        yield 'backslash path' => ['https://shop.example/\\evil.example', '/'];
        yield 'no referer' => [null, '/'];
    }
}
